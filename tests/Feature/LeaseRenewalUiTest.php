<?php

namespace Tests\Feature;

use App\Livewire\Admin\LeaseMappingManager;
use App\Livewire\User\MyLeases;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LeaseRenewalUiTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(SmsService::class)->shouldReceive('sendSms')->andReturn(true);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user']);
    }

    private function lease(array $overrides = [], ?User $tenant = null): StallMapping
    {
        static $n = 0;
        $n++;
        $floor = StallLocation::firstOrCreate(['name' => 'Floor 1'], ['sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'L-' . $n, 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);

        return StallMapping::create(array_merge([
            'user_id' => ($tenant ?? $this->tenant)->id,
            'stall_id' => $stall->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(40)->toDateString(),
            'is_active' => true,
            'payment_status' => 'good_standing',
            'renewal_status' => 'none',
        ], $overrides));
    }

    public function test_request_renewal_button_only_on_eligible_lease(): void
    {
        $this->lease();
        $this->lease(['end_date' => now()->addDays(200)->toDateString()]);

        $html = Livewire::actingAs($this->tenant)->test(MyLeases::class)->html();

        $this->assertSame(1, substr_count($html, 'Request renewal'));
    }

    public function test_tenant_submits_one_year_renewal(): void
    {
        $lease = $this->lease();

        Livewire::actingAs($this->tenant)
            ->test(MyLeases::class)
            ->call('openRenewal', $lease->id)
            ->set('renewalOption', '1y')
            ->set('renewalNote', 'Please renew.')
            ->call('submitRenewal')
            ->assertHasNoErrors()
            ->assertSee('Renewal requested — awaiting review');

        $lease->refresh();
        $this->assertSame('requested', $lease->renewal_status);
        $this->assertTrue($lease->requested_end_date->isSameDay($lease->end_date->copy()->addYear()));
    }

    public function test_rejected_lease_shows_reason(): void
    {
        $this->lease(['renewal_status' => 'rejected', 'renewal_rejection_reason' => 'Stall renovation planned.']);

        Livewire::actingAs($this->tenant)->test(MyLeases::class)->assertSee('Stall renovation planned.');
    }

    public function test_tenant_cannot_open_renewal_for_foreign_lease(): void
    {
        $foreign = $this->lease([], User::factory()->create(['role' => 'user']));

        Livewire::actingAs($this->tenant)
            ->test(MyLeases::class)
            ->call('openRenewal', $foreign->id)
            ->assertSet('renewalLeaseId', null)
            ->call('submitRenewal');

        $this->assertSame('none', $foreign->fresh()->renewal_status);
    }

    public function test_admin_filter_lists_only_requested_leases(): void
    {
        $requested = $this->lease(['renewal_status' => 'requested', 'requested_end_date' => now()->addYear()->toDateString()]);
        $plain = $this->lease();

        Livewire::actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->set('statusFilter', 'renewal_requested')
            ->assertSee($requested->stall->stall_number)
            ->assertDontSee($plain->stall->stall_number);
    }

    public function test_admin_approves_with_edited_date(): void
    {
        $lease = $this->lease(['renewal_status' => 'requested', 'requested_end_date' => now()->addDays(40)->addYear()->toDateString()]);
        $edited = Carbon::parse($lease->end_date)->addMonths(6)->toDateString();

        Livewire::actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->call('openDetailModal', $lease->id)
            ->assertSet('renewalEndDate', $lease->requested_end_date->toDateString())
            ->set('renewalEndDate', $edited)
            ->call('approveRenewal', $lease->id)
            ->assertHasNoErrors();

        $lease->refresh();
        $this->assertSame('approved', $lease->renewal_status);
        $this->assertSame($edited, $lease->end_date->toDateString());
    }

    public function test_admin_reject_requires_reason(): void
    {
        $lease = $this->lease(['renewal_status' => 'requested', 'requested_end_date' => now()->addYear()->toDateString()]);

        Livewire::actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->call('openDetailModal', $lease->id)
            ->set('renewalRejectReason', '')
            ->call('rejectRenewal', $lease->id)
            ->assertHasErrors('renewalRejectReason');

        $this->assertSame('requested', $lease->fresh()->renewal_status);
    }

    public function test_expiry_command_skips_lease_with_pending_renewal(): void
    {
        $this->lease([
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
            'renewal_status' => 'requested',
            'requested_end_date' => Carbon::today()->addYear()->toDateString(),
        ]);

        $this->artisan('stallm:notify-expiring-leases')->assertExitCode(0);

        $this->assertFalse(Notification::where('user_id', $this->tenant->id)->exists());
    }
}
