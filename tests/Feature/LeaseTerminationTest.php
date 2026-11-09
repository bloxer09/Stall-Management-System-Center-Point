<?php

namespace Tests\Feature;

use App\Livewire\Admin\LeaseMappingManager;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LeaseTerminationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private Stall $stall;
    private StallMapping $lease;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
        $this->lease = StallMapping::create([
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addMonths(9),
            'is_active' => true,
            'payment_status' => 'good_standing',
        ]);
    }

    private function invoice(Stall $stall, string $status = 'pending', float $amount = 4500): Invoice
    {
        static $n = 0;
        $n++;

        return Invoice::create([
            'invoice_number' => 'INV-T-' . $n,
            'user_id' => $this->tenant->id,
            'stall_id' => $stall->id,
            'total_amount' => $amount,
            'status' => $status,
            'due_date' => now()->subDays(5),
            'billing_period' => '2026-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
        ]);
    }

    private function terminate(string $reason, bool $ack = false)
    {
        return Livewire::actingAs($this->admin)
            ->test(LeaseMappingManager::class)
            ->call('openTerminate', $this->lease->id)
            ->set('terminationReason', $reason)
            ->set('acknowledgeBalance', $ack)
            ->call('terminateLease');
    }

    public function test_reason_is_required(): void
    {
        $this->terminate('short')->assertHasErrors(['terminationReason' => 'min']);

        $this->assertTrue($this->lease->fresh()->is_active);
    }

    public function test_unpaid_balance_requires_acknowledgement(): void
    {
        $this->invoice($this->stall);

        $this->terminate('Tenant closed the business.')
            ->assertHasErrors(['acknowledgeBalance'])
            ->assertSee('4,500.00');

        $this->assertTrue($this->lease->fresh()->is_active);
    }

    public function test_termination_with_balance_leaves_past_due(): void
    {
        $invoice = $this->invoice($this->stall);

        $this->terminate('Tenant closed the business.', true)->assertHasNoErrors();

        $lease = $this->lease->fresh();
        $this->assertFalse($lease->is_active);
        $this->assertSame('past_due', $lease->payment_status);
        $this->assertSame('Tenant closed the business.', $lease->termination_reason);
        $this->assertNotNull($lease->terminated_at);
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame('available', $this->stall->fresh()->status);
    }

    public function test_termination_without_balance_is_settled(): void
    {
        $this->invoice($this->stall, 'paid');

        $this->terminate('Lease ended by mutual agreement.')->assertHasNoErrors();

        $this->assertSame('settled', $this->lease->fresh()->payment_status);
    }

    public function test_tenant_notified_with_reason(): void
    {
        $this->terminate('Repeated violations of building rules.')->assertHasNoErrors();

        $notice = Notification::where('user_id', $this->tenant->id)->where('title', 'Lease terminated')->first();
        $this->assertNotNull($notice);
        $this->assertStringContainsString('Repeated violations of building rules.', $notice->message);
        Mail::assertSent(\App\Mail\GenericNotificationMail::class);
    }

    public function test_balance_counts_only_this_stall(): void
    {
        $other = Stall::create(['stall_number' => 'S-102', 'location_id' => $this->stall->location_id, 'price' => 3000, 'status' => 'occupied']);
        $this->invoice($other);
        $this->invoice($this->stall, 'pending', 1200);

        $this->assertSame(1200.0, app(LeaseService::class)->unpaidBalance($this->lease));
    }
}
