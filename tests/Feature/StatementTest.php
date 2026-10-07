<?php

namespace Tests\Feature;

use App\Livewire\Admin\TenantManager;
use App\Livewire\User\DocumentRepository;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\StatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class StatementTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Stall $stall;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
    }

    private function invoice(string $date, float $amount, string $status = 'pending'): Invoice
    {
        $this->n++;
        $invoice = Invoice::create([
            'invoice_number' => 'INV-S-' . $this->n,
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'total_amount' => $amount,
            'status' => $status,
            'due_date' => Carbon::parse($date)->addDays(10),
            'billing_period' => '2026-' . str_pad((string) $this->n, 2, '0', STR_PAD_LEFT),
        ]);
        $invoice->forceFill(['created_at' => Carbon::parse($date . ' 09:00')])->save();

        return $invoice;
    }

    private function payment(Invoice $invoice, string $date, float $amount, string $status = 'successful'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'invoice_id' => $invoice->id,
            'user_id' => $this->tenant->id,
            'gateway' => 'gcash',
            'gateway_transaction_id' => 'REF-' . $invoice->id,
            'amount' => $amount,
            'status' => $status,
            'paid_at' => Carbon::parse($date . ' 08:00'),
        ]);
    }

    public function test_running_balance(): void
    {
        $first = $this->invoice('2026-08-01', 4500, 'paid');
        $this->payment($first, '2026-08-02', 4500);
        $this->invoice('2026-08-03', 4500);
        $this->invoice('2026-08-04', 999, 'cancelled');

        $statement = app(StatementService::class)->forUser($this->tenant);

        $this->assertCount(3, $statement['lines']);
        $this->assertSame([4500.0, 0.0, 4500.0], array_column($statement['lines'], 'balance'));
        $this->assertSame(9000.0, $statement['charges']);
        $this->assertSame(4500.0, $statement['payments']);
        $this->assertSame(4500.0, $statement['balance']);
    }

    public function test_same_day_charge_before_credit(): void
    {
        $invoice = $this->invoice('2026-08-01', 3000, 'paid');
        // Paid earlier in the day than the invoice was issued: the charge still comes first.
        $this->payment($invoice, '2026-08-01', 3000);

        $lines = app(StatementService::class)->forUser($this->tenant)['lines'];

        $this->assertSame(3000.0, $lines[0]['charge']);
        $this->assertSame(3000.0, $lines[1]['credit']);
        $this->assertSame(0.0, $lines[1]['balance']);
    }

    public function test_failed_transactions_ignored(): void
    {
        $invoice = $this->invoice('2026-08-01', 3000);
        $this->payment($invoice, '2026-08-02', 3000, 'failed');

        $this->assertSame(3000.0, app(StatementService::class)->forUser($this->tenant)['balance']);
    }

    public function test_tenant_downloads_own_pdf(): void
    {
        $this->invoice('2026-08-01', 3000);

        $response = $this->actingAs($this->tenant)->get(route('statement.pdf', $this->tenant));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_tenant_cannot_download_other(): void
    {
        $other = User::factory()->create(['role' => 'user']);

        $this->actingAs($other)->get(route('statement.pdf', $this->tenant))->assertForbidden();
    }

    public function test_admin_can_download_any(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('statement.pdf', $this->tenant))->assertOk();
    }

    public function test_documents_page_shows_balance(): void
    {
        $this->invoice('2026-08-01', 2750);

        Livewire::actingAs($this->tenant)->test(DocumentRepository::class)
            ->assertSee('Account statement')
            ->assertSee('2,750.00')
            ->assertSee(route('statement.pdf', $this->tenant));
    }

    public function test_admin_statement_modal_shows_lines(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->invoice('2026-08-01', 4500);

        Livewire::actingAs($admin)->test(TenantManager::class)
            ->call('openStatement', $this->tenant->id)
            ->assertSee('INV-S-1')
            ->assertSee('4,500.00')
            ->assertSee(route('statement.pdf', $this->tenant));
    }
}
