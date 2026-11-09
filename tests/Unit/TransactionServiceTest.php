<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\PayMongoService;
use App\Services\TransactionService;
use App\Livewire\User\InvoicesCheckout;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant;
    protected Stall $stall;
    protected Invoice $invoice1;
    protected Invoice $invoice2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'user']);

        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
        ]);

        $this->stall = Stall::create([
            'stall_number' => 'C-301',
            'location_id' => $location->id,
            'price' => 4000.00,
            'status' => 'occupied',
        ]);

        $this->invoice1 = Invoice::create([
            'invoice_number' => 'INV-202608-0001',
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'total_amount' => 4000.00,
            'status' => 'pending',
            'due_date' => '2026-08-05',
        ]);
        InvoiceItem::create([
            'invoice_id' => $this->invoice1->id,
            'description' => 'August Rent',
            'amount' => 4000.00,
            'item_type' => 'rent',
        ]);

        $this->invoice2 = Invoice::create([
            'invoice_number' => 'INV-202609-0001',
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'total_amount' => 4000.00,
            'status' => 'pending',
            'due_date' => '2026-09-05',
        ]);
        InvoiceItem::create([
            'invoice_id' => $this->invoice2->id,
            'description' => 'September Rent',
            'amount' => 4000.00,
            'item_type' => 'rent',
        ]);
    }

    public function test_can_prepare_multi_invoice_checkout_session()
    {
        $mockPayMongo = $this->mock(PayMongoService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'success' => true,
                    'id' => 'cs_test_session_123',
                    'checkout_url' => 'https://checkout.paymongo.com/cs_test_session_123',
                    'data' => ['id' => 'cs_test_session_123'],
                ]);
        });

        $service = new TransactionService($mockPayMongo);

        $result = $service->prepareCheckoutSession(
            $this->tenant->id,
            [$this->invoice1->id, $this->invoice2->id],
            'http://localhost/success',
            'http://localhost/cancel'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('https://checkout.paymongo.com/cs_test_session_123', $result['checkout_url']);

        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $this->invoice1->id,
            'gateway_transaction_id' => 'cs_test_session_123',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $this->invoice2->id,
            'gateway_transaction_id' => 'cs_test_session_123',
            'status' => 'pending',
        ]);
    }

    private function pendingCheckout(string $sessionId = 'cs_reconcile'): void
    {
        foreach ([$this->invoice1, $this->invoice2] as $invoice) {
            PaymentTransaction::create([
                'invoice_id' => $invoice->id,
                'user_id' => $this->tenant->id,
                'gateway' => 'paymongo',
                'gateway_transaction_id' => $sessionId,
                'amount' => $invoice->total_amount,
                'status' => 'pending',
            ]);
        }
    }

    private function paidSession(string $id = 'cs_reconcile'): array
    {
        return ['success' => true, 'data' => [
            'id' => $id,
            'attributes' => ['status' => 'active', 'payments' => [[
                'id' => 'pay_verified',
                'attributes' => ['status' => 'paid', 'source' => ['type' => 'gcash']],
            ]]],
        ]];
    }

    public function test_opening_payments_recovers_a_paid_session_without_webhook(): void
    {
        $this->pendingCheckout();
        $this->mock(PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->once()->with('cs_reconcile')->andReturn($this->paidSession());

        Livewire::actingAs($this->tenant)->withQueryParams(['success' => '1'])
            ->test(InvoicesCheckout::class)->assertDontSee('Awaiting payment confirmation');

        $this->assertSame('paid', $this->invoice1->fresh()->status);
        $this->assertSame('paid', $this->invoice2->fresh()->status);
        $this->assertSame(2, PaymentTransaction::where('status', 'successful')->where('gateway', 'gcash')->count());
        $this->assertSame(0, app(TransactionService::class)->handleSuccessfulCheckout('cs_reconcile', 'gcash'));
    }

    public function test_polling_retries_unconfirmed_payment_and_updates_selected_invoice(): void
    {
        $this->pendingCheckout();
        $this->mock(PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->twice()->with('cs_reconcile')->andReturn(
                ['success' => true, 'data' => ['id' => 'cs_reconcile', 'attributes' => ['payments' => []]]],
                $this->paidSession()
            );
        $component = Livewire::actingAs($this->tenant)->test(InvoicesCheckout::class)
            ->call('openDetailModal', $this->invoice1->id)
            ->set('selectedInvoiceIds', [$this->invoice1->id]);
        $component->call('refreshPayments');
        $this->assertSame('pending', $this->invoice1->fresh()->status);
        Cache::forget('paymongo:verify:' . $this->tenant->id . ':cs_reconcile');
        $component->call('refreshPayments')->assertSet('selectedInvoiceIds', [])
            ->assertSet('selectedInvoice.status', 'paid');
    }

    public function test_success_query_alone_cannot_mark_an_invoice_paid(): void
    {
        $this->pendingCheckout();
        $this->mock(PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->once()->with('cs_reconcile')->andReturn(['success' => false]);
        Livewire::actingAs($this->tenant)->withQueryParams(['success' => '1'])
            ->test(InvoicesCheckout::class);
        $this->assertSame('pending', $this->invoice1->fresh()->status);
    }

    public function test_reconciliation_does_not_query_another_tenants_session(): void
    {
        $this->pendingCheckout();
        $this->mock(PayMongoService::class)->shouldNotReceive('retrieveCheckoutSession');
        $other = User::factory()->create(['role' => 'user']);
        $this->assertSame(0, app(TransactionService::class)->reconcilePendingCheckouts($other->id));
        $this->assertSame('pending', $this->invoice1->fresh()->status);
    }

    public function test_reconciliation_rejects_a_mismatched_session(): void
    {
        $this->pendingCheckout();
        $this->mock(PayMongoService::class)->shouldReceive('retrieveCheckoutSession')
            ->once()->with('cs_reconcile')->andReturn($this->paidSession('cs_other'));
        $this->assertSame(0, app(TransactionService::class)->reconcilePendingCheckouts($this->tenant->id));
        $this->assertSame('pending', $this->invoice1->fresh()->status);
    }

    public function test_can_handle_successful_checkout_fulfillment()
    {
        PaymentTransaction::create([
            'invoice_id' => $this->invoice1->id,
            'user_id' => $this->tenant->id,
            'gateway' => 'paymongo',
            'gateway_transaction_id' => 'cs_fulfilled_999',
            'amount' => 4000.00,
            'status' => 'pending',
        ]);

        PaymentTransaction::create([
            'invoice_id' => $this->invoice2->id,
            'user_id' => $this->tenant->id,
            'gateway' => 'paymongo',
            'gateway_transaction_id' => 'cs_fulfilled_999',
            'amount' => 4000.00,
            'status' => 'pending',
        ]);

        $service = app(TransactionService::class);
        $count = $service->handleSuccessfulCheckout('cs_fulfilled_999', 'gcash', ['paid' => true]);

        $this->assertEquals(2, $count);
        $this->assertEquals('paid', $this->invoice1->fresh()->status);
        $this->assertEquals('paid', $this->invoice2->fresh()->status);
        $this->assertNotNull($this->invoice1->fresh()->paid_at);

        $this->assertDatabaseHas('payment_transactions', [
            'invoice_id' => $this->invoice1->id,
            'gateway' => 'gcash',
            'status' => 'successful',
        ]);
    }
}
