<?php

namespace Tests\Feature;

use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StallSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_admin_can_access_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_regular_user_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_stall_location_and_stalls_creation()
    {
        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
            'grid_rows' => 6,
            'grid_cols' => 8,
        ]);

        $stall = Stall::create([
            'stall_number' => 'A-101',
            'location_id' => $location->id,
            'price' => 5000.00,
            'status' => 'available',
        ]);

        $this->assertDatabaseHas('stalls', [
            'stall_number' => 'A-101',
            'price' => 5000.00,
        ]);
    }
}
