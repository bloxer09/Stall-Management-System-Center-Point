<?php

namespace Tests\Feature;

use App\Livewire\User\UserDashboard;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallApplication;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private StallLocation $floor;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'email' => 'juan@mail.com']);
        $this->floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
    }

    private function stall(string $number, string $status = 'available'): Stall
    {
        return Stall::create(['stall_number' => $number, 'location_id' => $this->floor->id, 'price' => 3000, 'status' => $status]);
    }

    private function lease(int $daysLeft): StallMapping
    {
        return StallMapping::create([
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall('G-01', 'occupied')->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays($daysLeft)->toDateString(),
            'is_active' => true,
            'payment_status' => 'good_standing',
            'renewal_status' => 'none',
        ]);
    }

    private function application(array $overrides): StallApplication
    {
        return StallApplication::create(array_merge([
            'applicant_name' => 'Juan', 'applicant_email' => 'juan@mail.com', 'applicant_phone' => '0917',
            'business_name' => 'Biz', 'business_type' => 'Retail', 'status' => 'pending',
        ], $overrides));
    }

    public function test_tenant_without_lease_sees_browse_cta(): void
    {
        Livewire::actingAs($this->tenant)->test(UserDashboard::class)
            ->assertSee("You don't have a stall yet", false)
            ->assertSee(route('stalls.browse'));
    }

    public function test_my_applications_lists_only_applications_linked_to_the_account(): void
    {
        $this->application(['business_name' => 'Linked By Id', 'user_id' => $this->tenant->id, 'applicant_email' => 'other-address@mail.com', 'preferred_stall_id' => $this->stall('A-1')->id]);
        $this->application(['business_name' => 'Rejected Linked', 'user_id' => $this->tenant->id, 'status' => 'rejected', 'rejection_reason' => 'Food stalls only.', 'preferred_stall_id' => $this->stall('A-2')->id]);
        $this->application(['business_name' => 'Same Email Unlinked', 'applicant_email' => 'JUAN@MAIL.COM', 'preferred_stall_id' => $this->stall('A-3')->id]);

        Livewire::actingAs($this->tenant)->test(UserDashboard::class)
            ->assertSee('My applications')
            ->assertSee('Linked By Id')
            ->assertSee('Food stalls only.')
            ->assertDontSee('Same Email Unlinked');
    }

    public function test_lease_countdown_and_renew_button(): void
    {
        $lease = $this->lease(45);

        Livewire::actingAs($this->tenant)->test(UserDashboard::class)
            ->assertSee('Ends in 45 days')
            ->assertSee('Request renewal')
            ->call('openRenewal', $lease->id)
            ->set('renewalMonths', 6)
            ->call('submitRenewal')
            ->assertHasNoErrors();

        $this->assertSame('requested', $lease->fresh()->renewal_status);
    }

    public function test_next_bill_due_with_overdue_badge(): void
    {
        $lease = $this->lease(200);
        Invoice::create(['invoice_number' => 'INV-NEXT-1', 'user_id' => $this->tenant->id, 'stall_id' => $lease->stall_id, 'total_amount' => 3456.78, 'status' => 'pending', 'due_date' => now()->subDays(2)->toDateString(), 'billing_period' => '2026-09']);

        Livewire::actingAs($this->tenant)->test(UserDashboard::class)
            ->assertSee('Next bill due')
            ->assertSee('3,456.78')
            ->assertSee('Overdue')
            ->assertSee(route('user.payments'));
    }
}
