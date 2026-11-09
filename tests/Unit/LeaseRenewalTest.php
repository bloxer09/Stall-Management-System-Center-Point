<?php

namespace Tests\Unit;

use App\Mail\GenericNotificationMail;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\LeaseService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

class LeaseRenewalTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private User $admin;
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->mock(SmsService::class, fn ($m) => $m->shouldReceive('enabled')->andReturn(true))->shouldReceive('sendSms')->andReturnUsing(function ($n, $m) {
            $this->sms[] = $m;
            return true;
        });
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'phone' => '09170000001']);
    }

    private function lease(array $overrides = [], ?User $tenant = null): StallMapping
    {
        static $n = 0;
        $n++;
        $floor = StallLocation::firstOrCreate(['name' => 'Floor 1'], ['sort_order' => 1]);
        $stall = Stall::create(['stall_number' => 'R-' . $n, 'location_id' => $floor->id, 'price' => 3000, 'status' => 'occupied']);

        return StallMapping::create(array_merge([
            'user_id' => ($tenant ?? $this->tenant)->id,
            'stall_id' => $stall->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'is_active' => true,
            'payment_status' => 'good_standing',
            'renewal_status' => 'none',
        ], $overrides));
    }

    private function service(): LeaseService
    {
        return app(LeaseService::class);
    }

    public function test_tenant_can_request_renewal_on_own_eligible_lease(): void
    {
        $lease = $this->lease();
        $requested = $lease->end_date->copy()->addYear();

        $this->service()->requestRenewal($lease, $this->tenant, $requested, 'Business is doing well.');

        $lease->refresh();
        $this->assertSame('requested', $lease->renewal_status);
        $this->assertTrue($lease->requested_end_date->isSameDay($requested));
        $this->assertSame('Business is doing well.', $lease->renewal_note);
        $this->assertTrue(Notification::where('user_id', $this->admin->id)->where('type', 'renewal')->exists());
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'StallMapping', 'entity_id' => $lease->id]);
    }

    public function test_cannot_request_on_another_tenants_lease(): void
    {
        $lease = $this->lease([], User::factory()->create(['role' => 'user']));

        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());
    }

    public function test_cannot_request_on_inactive_lease(): void
    {
        $lease = $this->lease(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());
    }

    public function test_cannot_request_when_lease_ends_in_more_than_60_days(): void
    {
        $lease = $this->lease(['end_date' => now()->addDays(90)->toDateString()]);

        $this->assertFalse($this->service()->isRenewalEligible($lease));
        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());
    }

    public function test_cannot_request_twice_while_pending(): void
    {
        $lease = $this->lease();
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());

        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease->fresh(), $this->tenant, $lease->end_date->copy()->addYears(2));
    }

    public function test_requested_date_must_be_after_current_end(): void
    {
        $lease = $this->lease();

        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy());
    }

    public function test_can_request_again_after_rejection_or_previous_approval(): void
    {
        foreach (['rejected', 'approved'] as $status) {
            $lease = $this->lease(['renewal_status' => $status]);
            $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addMonths(6));
            $this->assertSame('requested', $lease->fresh()->renewal_status);
        }
    }

    public function test_review_creates_one_invoice_and_payment_extends_once(): void
    {
        $lease = $this->lease(['agreed_monthly_rate' => '2500.25']);
        $originalEnd = $lease->end_date->toDateString();
        $newEnd = $lease->end_date->copy()->addMonthsNoOverflow(3);
        $service = $this->service();
        $service->requestRenewal($lease, $this->tenant, $newEnd);
        $service->approveRenewal($lease->fresh(), $this->admin);
        $invoice = $lease->fresh()->renewalInvoice;
        $this->assertSame('7500.75', $invoice->total_amount);
        $this->assertSame($originalEnd, $lease->fresh()->end_date->toDateString());
        $count = Notification::count();
        $service->approveRenewal($lease->fresh(), $this->admin);
        $this->assertSame(1, \App\Models\Invoice::where('is_renewal', true)->count());
        $this->assertSame($count, Notification::count());
        app(\App\Services\InvoiceService::class)->onInvoicePaid($invoice);
        $this->assertSame('awaiting_payment', $lease->fresh()->renewal_status);
        app(\App\Services\InvoiceService::class)->markAsPaid($invoice);
        $count = Notification::count();
        app(\App\Services\InvoiceService::class)->markAsPaid($invoice);
        $this->assertTrue($lease->fresh()->end_date->isSameDay($newEnd));
        $this->assertSame('approved', $lease->fresh()->renewal_status);
        $this->assertSame($count, Notification::count());
        $this->assertSame(1, \App\Models\PaymentTransaction::where('invoice_id', $invoice->id)->count());
    }

    public function test_paymongo_renewal_confirmation_extends_once_and_preserves_prior_rent(): void
    {
        $this->travelTo(Carbon::parse('2026-01-01'));
        $lease = $this->lease(['end_date' => '2026-01-31', 'rent_billing_mode' => 'monthly']);
        $service = $this->service();
        $service->requestRenewal($lease, $this->tenant, Carbon::parse('2026-02-28'));
        $service->approveRenewal($lease->fresh(), $this->admin);
        $invoice = $lease->fresh()->renewalInvoice;
        \App\Models\PaymentTransaction::create([
            'invoice_id' => $invoice->id, 'user_id' => $this->tenant->id,
            'amount' => $invoice->total_amount, 'gateway' => 'paymongo',
            'gateway_transaction_id' => 'cs_renewal', 'status' => 'pending',
        ]);
        $transactions = app(\App\Services\TransactionService::class);
        $this->assertSame(1, $transactions->handleSuccessfulCheckout('cs_renewal', 'gcash'));
        $this->assertSame(0, $transactions->handleSuccessfulCheckout('cs_renewal', 'gcash'));
        $this->assertSame('2026-02-28', $lease->fresh()->end_date->toDateString());
        $billing = app(\App\Services\InvoiceService::class);
        $this->assertNull($billing->generateMonthlyRentalInvoice($lease->fresh(), '2026-02'));
        $this->assertNotNull($billing->generateMonthlyRentalInvoice($lease->fresh(), '2026-01'));
    }

    public function test_reviewed_terms_cannot_be_rejected_or_requested_again(): void
    {
        $lease = $this->lease();
        $service = $this->service();
        $service->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addMonthsNoOverflow(1));
        $service->approveRenewal($lease->fresh(), $this->admin);
        $this->assertFalse($service->isRenewalEligible($lease->fresh()));
        $this->assertFalse($service->canTerminate($lease->fresh()));
        $this->expectException(InvalidArgumentException::class);
        $service->rejectRenewal($lease->fresh(), $this->admin, 'Changed our mind');
    }

    public function test_partial_month_request_is_rejected(): void
    {
        $lease = $this->lease();
        $this->expectException(InvalidArgumentException::class);
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addDays(15));
    }

    public function test_second_renewal_keeps_first_invoice_coverage(): void
    {
        $lease = $this->lease();
        $service = $this->service();
        $service->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addMonthsNoOverflow(1));
        $service->approveRenewal($lease->fresh(), $this->admin);
        $first = $lease->fresh()->renewalInvoice;
        app(\App\Services\InvoiceService::class)->markAsPaid($first);
        $this->travelTo($lease->fresh()->end_date->copy()->subDays(30));
        $lease->refresh();
        $service->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addMonthsNoOverflow(2));
        $service->approveRenewal($lease->fresh(), $this->admin);
        $second = $lease->fresh()->renewalInvoice;
        $this->assertNotSame($first->id, $second->id);
        $this->assertTrue($second->rent_period_start->isSameDay($first->rent_period_end));
        $this->assertSame('paid', $first->fresh()->status);
    }

    public function test_approve_moves_end_date_and_notifies_tenant(): void
    {
        $lease = $this->lease();
        $requested = $lease->end_date->copy()->addYear();
        $this->service()->requestRenewal($lease, $this->tenant, $requested);

        $this->service()->approveRenewal($lease->fresh(), $this->admin);

        $this->assertSame('awaiting_payment', $lease->fresh()->renewal_status);
        app(\App\Services\InvoiceService::class)->markAsPaid($lease->fresh()->renewalInvoice);

        $lease->refresh();
        $this->assertTrue($lease->end_date->isSameDay($requested));
        $this->assertSame('approved', $lease->renewal_status);
        $this->assertTrue(Notification::where('user_id', $this->tenant->id)->where('type', 'renewal')->exists());
        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo($this->tenant->email));
        $this->assertNotEmpty($this->sms);
    }

    public function test_approve_respects_override_date(): void
    {
        $lease = $this->lease();
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());
        $override = $lease->end_date->copy()->addMonths(6);

        $this->service()->approveRenewal($lease->fresh(), $this->admin, $override);

        app(\App\Services\InvoiceService::class)->markAsPaid($lease->fresh()->renewalInvoice);

        $this->assertTrue($lease->fresh()->end_date->isSameDay($override));
    }

    public function test_approve_requires_pending_request(): void
    {
        $lease = $this->lease();

        $this->expectException(InvalidArgumentException::class);
        $this->service()->approveRenewal($lease, $this->admin);
    }

    public function test_reject_requires_reason_and_notifies(): void
    {
        $lease = $this->lease();
        $this->service()->requestRenewal($lease, $this->tenant, $lease->end_date->copy()->addYear());

        try {
            $this->service()->rejectRenewal($lease->fresh(), $this->admin, '   ');
            $this->fail('Empty reason should be rejected.');
        } catch (InvalidArgumentException) {
            $this->assertSame('requested', $lease->fresh()->renewal_status);
        }

        $this->service()->rejectRenewal($lease->fresh(), $this->admin, 'Stall is scheduled for renovation.');

        $lease->refresh();
        $this->assertSame('rejected', $lease->renewal_status);
        $this->assertSame('Stall is scheduled for renovation.', $lease->renewal_rejection_reason);
        $this->assertTrue(Notification::where('user_id', $this->tenant->id)->where('type', 'renewal')->exists());
        Mail::assertSent(GenericNotificationMail::class, fn ($m) => $m->hasTo($this->tenant->email));
    }
}
