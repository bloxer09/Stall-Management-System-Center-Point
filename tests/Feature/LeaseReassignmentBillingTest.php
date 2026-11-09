<?php

namespace Tests\Feature;

use App\Livewire\Admin\LeaseMappingManager;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\LeaseService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaseReassignmentBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-10-09 10:37:44');
        $this->tenant = User::factory()->create(['role' => 'user']);
        $floor = StallLocation::create(['name' => 'Food Court', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'F-01', 'location_id' => $floor->id, 'price' => 5000, 'status' => 'available']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function tenants(): array
    {
        return [['same'], ['different']];
    }

    #[DataProvider('tenants')]
    public function test_reassignment_after_cancelled_reservation_keeps_history_and_creates_a_fresh_bill(string $tenantChoice): void
    {
        $leases = app(LeaseService::class);
        $first = $leases->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2027-10-09');
        $oldInvoice = $first->initialInvoice;
        $leases->terminateLease($first, 'Reservation cancelled');
        $tenant = $tenantChoice === 'same' ? $this->tenant : User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        Livewire::actingAs($admin)->test(LeaseMappingManager::class)->call('openModal')
            ->set('user_id', $tenant->id)->set('stall_id', $this->stall->id)->call('saveMapping')
            ->assertHasNoErrors()->assertSet('showModal', false);
        $newInvoice = Invoice::latest('id')->first();
        $this->assertSame(2, Invoice::count());
        $this->assertSame('cancelled', $oldInvoice->fresh()->status);
        $this->assertSame('pending', $newInvoice->status);
        $this->assertSame($tenant->id, $newInvoice->user_id);
        $this->assertNotSame($oldInvoice->invoice_number, $newInvoice->invoice_number);
        $this->assertNotSame($first->id, $newInvoice->stall_mapping_id);
        $this->assertSame('2026-10', $newInvoice->billing_period);
        $this->assertSame('reserved', $this->stall->fresh()->status);
        $this->assertNull(app(InvoiceService::class)->generateMonthlyRentalInvoice($newInvoice->lease, '2026-10'));
    }

    public function test_paid_history_does_not_block_new_initial_or_monthly_invoices(): void
    {
        $leases = app(LeaseService::class);
        $invoices = app(InvoiceService::class);
        $first = $leases->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2027-10-09');
        $invoices->markAsPaid($first->initialInvoice);
        $oldMonthly = $invoices->generateMonthlyRentalInvoice($first, '2026-11');
        $leases->terminateLease($first->fresh(), 'Tenant left');
        $second = $leases->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2027-10-09');
        $newMonthly = $invoices->generateMonthlyRentalInvoice($second, '2026-11');
        $this->assertSame('paid', $first->initialInvoice->fresh()->status);
        $this->assertSame('pending', $oldMonthly->fresh()->status);
        $this->assertSame($second->id, $newMonthly->stall_mapping_id);
        $this->assertNotSame($oldMonthly->invoice_number, $newMonthly->invoice_number);
        $this->assertNull($invoices->generateMonthlyRentalInvoice($second, '2026-11'));
        $second->update(['is_active' => true, 'payment_status' => 'past_due']);
        $invoices->markAsPaid($oldMonthly);
        $this->assertSame('past_due', $second->fresh()->payment_status);
    }

    public function test_database_still_rejects_two_bills_for_one_lease_and_month(): void
    {
        $lease = app(LeaseService::class)->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2027-10-09');
        $this->expectException(UniqueConstraintViolationException::class);
        Invoice::create([
            'invoice_number' => 'DUPLICATE', 'user_id' => $this->tenant->id, 'stall_id' => $this->stall->id,
            'stall_mapping_id' => $lease->id, 'billing_period' => '2026-10', 'total_amount' => 5000,
            'status' => 'pending', 'due_date' => '2026-10-16',
        ]);
    }

    public function test_migration_links_existing_initial_and_monthly_bills(): void
    {
        $lease = app(LeaseService::class)->createLease($this->tenant->id, $this->stall->id, '2026-10-09', '2027-10-09');
        $monthly = app(InvoiceService::class)->generateMonthlyRentalInvoice($lease, '2026-11');
        $migration = require database_path('migrations/2026_10_09_000002_scope_rental_invoice_periods_to_leases.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('invoices', 'stall_mapping_id'));
        $migration->up();
        $this->assertSame($lease->id, $lease->initialInvoice->fresh()->stall_mapping_id);
        $this->assertSame($lease->id, $monthly->fresh()->stall_mapping_id);
    }
}
