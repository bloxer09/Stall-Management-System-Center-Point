<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\LeaseService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CancelExpiredReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 10, 10)->startOfDay());
    }

    private function reservation(string $due = '2026-10-09'): array
    {
        $tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::firstOrCreate(['name' => 'Expiry Floor'], ['sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'EXP-' . $tenant->id, 'location_id' => $floor->id, 'price' => 1000, 'status' => 'reserved']);
        $lease = StallMapping::create([
            'user_id' => $tenant->id, 'stall_id' => $stall->id, 'is_active' => false,
            'payment_status' => 'pending_initial', 'start_date' => today(), 'end_date' => today()->addYear(),
        ]);
        $invoice = Invoice::create([
            'user_id' => $tenant->id, 'stall_id' => $stall->id, 'stall_mapping_id' => $lease->id,
            'invoice_number' => 'EXP-INV-' . $tenant->id, 'status' => 'pending', 'due_date' => $due, 'total_amount' => 1000,
        ]);
        $lease->update(['initial_invoice_id' => $invoice->id]);
        return [$tenant, $stall, $lease, $invoice];
    }

    public function test_overdue_reservation_is_cancelled_and_notified_once(): void
    {
        User::factory()->create(['role' => 'admin']);
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        $this->artisan('stallm:cancel-expired-reservations')->expectsOutput('Cancelled 1 expired reservation(s).')->assertSuccessful();
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame('available', $stall->fresh()->status);
        $this->assertSame('settled', $lease->fresh()->payment_status);
        $this->assertFalse($lease->fresh()->is_active);
        $this->assertNotNull($lease->fresh()->terminated_at);
        $this->assertStringContainsString('seven-day payment deadline', $lease->fresh()->termination_reason);
        $this->assertSame(2, Notification::count());
        $this->assertSame(1, AuditLog::where('entity_type', 'StallMapping')->where('entity_id', $lease->id)->count());
        Mail::assertSentCount(1);
        $this->artisan('stallm:cancel-expired-reservations')->expectsOutput('Cancelled 0 expired reservation(s).')->assertSuccessful();
        $this->assertSame(2, Notification::count());
        Mail::assertSentCount(1);
        $this->assertDatabaseHas('users', ['id' => $tenant->id]);
    }

    public function test_due_today_future_paid_active_and_renewal_invoices_are_preserved(): void
    {
        $cases = [$this->reservation('2026-10-10'), $this->reservation('2026-10-11'), $this->reservation(), $this->reservation(), $this->reservation()];
        $cases[2][3]->update(['status' => 'paid', 'paid_at' => now()]);
        $cases[3][2]->update(['is_active' => true, 'payment_status' => 'good_standing']);
        $cases[3][1]->update(['status' => 'occupied']);
        $cases[4][3]->update(['is_renewal' => true]);
        $this->artisan('stallm:cancel-expired-reservations')->expectsOutput('Cancelled 0 expired reservation(s).')->assertSuccessful();
        foreach ($cases as [$tenant, $stall, $lease, $invoice]) {
            $this->assertNull($lease->fresh()->terminated_at);
            $this->assertNotSame('cancelled', $invoice->fresh()->status);
        }
        $this->assertSame(0, Notification::count());
    }

    public function test_payment_confirmed_first_prevents_cancellation(): void
    {
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        app(InvoiceService::class)->markAsPaid($invoice);
        $this->artisan('stallm:cancel-expired-reservations')->expectsOutput('Cancelled 0 expired reservation(s).')->assertSuccessful();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertTrue($lease->fresh()->is_active);
        $this->assertSame('occupied', $stall->fresh()->status);
    }

    public function test_late_gateway_confirmation_is_recorded_for_review_without_reactivation(): void
    {
        User::factory()->create(['role' => 'admin']);
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        $transaction = PaymentTransaction::create([
            'user_id' => $tenant->id, 'invoice_id' => $invoice->id, 'gateway' => 'paymongo',
            'gateway_transaction_id' => 'late-checkout', 'status' => 'pending', 'amount' => 1000,
        ]);
        $staleLease = $lease->fresh();
        $this->artisan('stallm:cancel-expired-reservations')->assertSuccessful();
        app(TransactionService::class)->handleSuccessfulCheckout('late-checkout');
        app(LeaseService::class)->activateLeaseOnPayment($staleLease);
        $this->assertSame('successful', $transaction->fresh()->status);
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertFalse($lease->fresh()->is_active);
        $this->assertSame('available', $stall->fresh()->status);
        $this->assertSame(2, Notification::where('title', 'like', 'Payment%')->count());
        app(TransactionService::class)->handleSuccessfulCheckout('late-checkout');
        $this->assertSame(2, Notification::where('title', 'like', 'Payment%')->count());
    }

    public function test_another_assignment_is_not_released_by_expiry(): void
    {
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        StallMapping::create([
            'user_id' => User::factory()->create(['role' => 'user'])->id, 'stall_id' => $stall->id,
            'is_active' => false, 'payment_status' => 'pending_initial', 'start_date' => today(), 'end_date' => today()->addYear(),
        ]);
        $this->artisan('stallm:cancel-expired-reservations')->assertSuccessful();
        $this->assertSame('reserved', $stall->fresh()->status);
        $this->assertSame('cancelled', $invoice->fresh()->status);
    }

    public function test_cancelled_initial_invoice_cannot_be_paid_by_cash_and_stall_can_be_reassigned(): void
    {
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        $this->artisan('stallm:cancel-expired-reservations')->assertSuccessful();
        app(InvoiceService::class)->markAsPaid($invoice);
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertDatabaseCount('payment_transactions', 0);

        $nextTenant = User::factory()->create(['role' => 'user']);
        $nextLease = app(LeaseService::class)->createLease(
            $nextTenant->id, $stall->id, today()->toDateString(), today()->addYear()->toDateString()
        );
        $this->assertSame('pending_initial', $nextLease->payment_status);
        $this->assertSame('reserved', $stall->fresh()->status);
        $this->assertFalse(app(LeaseService::class)->cancelExpiredReservation($invoice->id));
        $this->assertSame('reserved', $stall->fresh()->status);
        $this->assertFalse($lease->fresh()->is_active);
    }

    public function test_mismatched_initial_invoice_cannot_cancel_a_reservation(): void
    {
        [$tenant, $stall, $lease, $invoice] = $this->reservation();
        $invoice->update(['user_id' => User::factory()->create(['role' => 'user'])->id]);
        $this->artisan('stallm:cancel-expired-reservations')->expectsOutput('Cancelled 0 expired reservation(s).')->assertSuccessful();
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertSame('reserved', $stall->fresh()->status);
        $this->assertNull($lease->fresh()->terminated_at);
        $this->assertDatabaseCount('notifications', 0);
    }
}
