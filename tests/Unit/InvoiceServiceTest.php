<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;
    protected User $tenant;
    protected Stall $stall;
    protected StallMapping $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(InvoiceService::class);
        $this->tenant = User::factory()->create(['role' => 'user']);

        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
        ]);

        $this->stall = Stall::create([
            'stall_number' => 'B-201',
            'location_id' => $location->id,
            'price' => 7500.00,
            'status' => 'occupied',
        ]);

        $this->lease = StallMapping::create([
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);
    }

    public function test_can_generate_monthly_rental_invoice()
    {
        $period = '2026-08';
        $invoice = $this->invoiceService->generateMonthlyRentalInvoice($this->lease, $period);

        $this->assertNotNull($invoice);
        $this->assertEquals(7500.00, $invoice->total_amount);
        $this->assertEquals('pending', $invoice->status);
        $this->assertEquals($period, $invoice->billing_period);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals('rent', $invoice->items->first()->item_type);
    }

    public function test_will_not_generate_duplicate_invoice_for_same_period()
    {
        $period = '2026-08';
        $first = $this->invoiceService->generateMonthlyRentalInvoice($this->lease, $period);
        $second = $this->invoiceService->generateMonthlyRentalInvoice($this->lease, $period);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertEquals(1, Invoice::where('stall_id', $this->stall->id)->where('billing_period', $period)->count());
    }

    public function test_can_generate_all_monthly_invoices()
    {
        $count = $this->invoiceService->generateAllMonthlyInvoices('2026-09');
        $this->assertEquals(1, $count);
    }

    public function test_can_record_manual_cash_payment()
    {
        $invoice = $this->invoiceService->recordManualCashPayment(
            $this->tenant->id,
            $this->stall->id,
            7500.00,
            'CASH-REF-12345',
            'Over the counter cash'
        );

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'cash',
            'gateway_transaction_id' => 'CASH-REF-12345',
            'status' => 'successful',
        ]);
    }

    public function test_can_mark_existing_invoice_as_paid()
    {
        $invoice = $this->invoiceService->generateMonthlyRentalInvoice($this->lease, '2026-10');
        $this->assertTrue($invoice->isPending());

        $this->invoiceService->markAsPaid($invoice, 'office_cash', 'OFFICE-101');

        $this->assertTrue($invoice->fresh()->isPaid());
        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $invoice->id,
            'gateway' => 'office_cash',
            'gateway_transaction_id' => 'OFFICE-101',
            'status' => 'successful',
        ]);
    }

    public function test_can_cancel_invoice()
    {
        $invoice = $this->invoiceService->generateMonthlyRentalInvoice($this->lease, '2026-11');
        $this->invoiceService->cancelInvoice($invoice, 'Adjusted fee');

        $this->assertEquals('cancelled', $invoice->fresh()->status);
        $this->assertStringContainsString('Adjusted fee', $invoice->fresh()->notes);
    }
}
