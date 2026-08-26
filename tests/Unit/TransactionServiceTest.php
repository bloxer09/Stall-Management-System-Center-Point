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
