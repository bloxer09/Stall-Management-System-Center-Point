<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\LeaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LeaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaseService $leaseService;
    protected User $tenant;
    protected Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leaseService = app(LeaseService::class);
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

    public function test_can_create_active_lease_and_update_stall_status()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $this->assertTrue($lease->is_active);
        $this->assertEquals('occupied', $this->stall->fresh()->status);
        $this->assertDatabaseHas('stall_mappings', [
            'id' => $lease->id,
            'user_id' => $this->tenant->id,
            'stall_id' => $this->stall->id,
            'is_active' => true,
        ]);
    }

    public function test_cannot_create_lease_on_already_active_stall()
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

    public function test_can_request_and_approve_renewal()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addMonths(6)->toDateString()
        );

        $requestedDate = now()->addMonths(12)->toDateString();
        $this->leaseService->requestRenewal($lease, $requestedDate);

        $this->assertEquals('requested', $lease->fresh()->renewal_status);
        $this->assertEquals($requestedDate, $lease->fresh()->requested_end_date->toDateString());

        $this->leaseService->approveRenewal($lease->fresh());

        $updated = $lease->fresh();
        $this->assertEquals('approved', $updated->renewal_status);
        $this->assertEquals($requestedDate, $updated->end_date->toDateString());
        $this->assertNull($updated->requested_end_date);
    }

    public function test_can_reject_renewal()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addMonths(6)->toDateString()
        );

        $originalEndDate = $lease->end_date->toDateString();
        $this->leaseService->requestRenewal($lease, now()->addMonths(12)->toDateString());
        $this->leaseService->rejectRenewal($lease->fresh());

        $updated = $lease->fresh();
        $this->assertEquals('rejected', $updated->renewal_status);
        $this->assertEquals($originalEndDate, $updated->end_date->toDateString());
        $this->assertNull($updated->requested_end_date);
    }

    public function test_can_terminate_lease_and_free_stall()
    {
        $lease = $this->leaseService->createLease(
            $this->tenant->id,
            $this->stall->id,
            now()->toDateString(),
            now()->addYear()->toDateString()
        );

        $this->assertEquals('occupied', $this->stall->fresh()->status);

        $this->leaseService->terminateLease($lease);

        $this->assertFalse($lease->fresh()->is_active);
        $this->assertEquals('available', $this->stall->fresh()->status);
    }
}
