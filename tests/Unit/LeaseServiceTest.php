<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LeaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaseService $leaseService;
    protected InvoiceService $invoiceService;
    protected User $tenant;
    protected Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leaseService = app(LeaseService::class);
        $this->invoiceService = app(InvoiceService::class);
        $this->tenant = User::factory()->create(['role' => 'user']);

        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
        ]);

        $this->stall = Stall::create([
            'stall_number' => 'S-101',
            'location_id' => $location->id,
            'price' => 5000.00,
            'status' => 'available',
        ]);
    }

    public function test_can_create_lease_in_pending_initial_status_and_reserve_stall()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $this->assertFalse($lease->is_active);
        $this->assertEquals('pending_initial', $lease->payment_status);
        $this->assertEquals('reserved', $this->stall->fresh()->status);
        $this->assertNotNull($lease->initial_invoice_id);

        $this->assertDatabaseHas('invoices', [
            'id' => $lease->initial_invoice_id,
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'status' => 'pending',
            'total_amount' => 5000.00,
        ]);
    }

    public function test_paying_initial_invoice_activates_lease_and_occupies_stall()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $invoice = Invoice::findOrFail($lease->initial_invoice_id);
        $this->invoiceService->markAsPaid($invoice, 'office_cash');

        $updatedLease = $lease->fresh();
        $this->assertTrue($updatedLease->is_active);
        $this->assertEquals('good_standing', $updatedLease->payment_status);
        $this->assertEquals('occupied', $this->stall->fresh()->status);
    }

    public function test_paying_monthly_rental_invoice_renews_and_maintains_good_standing()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addMonths(1)->toDateString()
        );

        $initialInvoice = Invoice::findOrFail($lease->initial_invoice_id);
        $this->invoiceService->markAsPaid($initialInvoice, 'office_cash');

        // Generate next monthly invoice
        $monthlyInvoice = $this->invoiceService->generateMonthlyRentalInvoice($lease->fresh(), now()->addMonths(1)->format('Y-m'));
        $this->assertNotNull($monthlyInvoice);

        $oldEndDate = $lease->fresh()->end_date;

        // Tenant pays monthly invoice
        $this->invoiceService->markAsPaid($monthlyInvoice, 'paymongo');

        $renewedLease = $lease->fresh();
        $this->assertEquals('good_standing', $renewedLease->payment_status);
        $this->assertTrue($renewedLease->end_date->gt($oldEndDate));
    }

    public function test_cannot_create_lease_on_already_assigned_stall()
    {
        $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $secondTenant = User::factory()->create(['role' => 'user']);

        $this->expectException(InvalidArgumentException::class);
        $this->leaseService->createLease(
            $secondTenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );
    }

    public function test_can_terminate_lease_and_free_stall()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $this->assertEquals('reserved', $this->stall->fresh()->status);

        $this->leaseService->terminateLease($lease);

        $this->assertFalse($lease->fresh()->is_active);
        $this->assertEquals('available', $this->stall->fresh()->status);
    }
}
