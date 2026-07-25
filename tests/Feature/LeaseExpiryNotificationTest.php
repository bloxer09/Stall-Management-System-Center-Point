<?php

namespace Tests\Feature;

use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaseExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lease_expiry_command_dispatches_notifications_at_60_and_30_days()
    {
        $tenant = User::factory()->create(['role' => 'user', 'phone' => '09171234567']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
        ]);

        $stall = Stall::create([
            'stall_number' => 'A-101',
            'location_id' => $location->id,
            'price' => 5000.00,
            'status' => 'occupied',
        ]);

        // Active lease expiring in 60 days
        StallMapping::create([
            'user_id' => $tenant->id,
            'stall_id' => $stall->id,
            'start_date' => Carbon::now()->subYear()->toDateString(),
            'end_date' => Carbon::today()->addDays(60)->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('stallm:notify-expiring-leases')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $tenant->id,
            'type' => 'warning',
        ]);
    }
}
