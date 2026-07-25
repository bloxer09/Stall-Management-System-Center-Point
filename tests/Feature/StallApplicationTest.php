<?php

namespace Tests\Feature;

use App\Livewire\Public\StallReservation;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StallApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_page_loads()
    {
        $response = $this->get('/reservation');
        $response->assertStatus(200);
    }

    public function test_stall_application_can_be_submitted()
    {
        User::factory()->create(['role' => 'admin']);

        $location = StallLocation::create([
            'name' => 'Floor 1',
            'sort_order' => 1,
        ]);

        $stall = Stall::create([
            'stall_number' => 'B-201',
            'location_id' => $location->id,
            'price' => 6000.00,
            'status' => 'available',
        ]);

        Livewire::test(StallReservation::class)
            ->set('applicant_name', 'Maria Santos')
            ->set('applicant_email', 'maria@example.com')
            ->set('applicant_phone', '09181234567')
            ->set('business_name', 'Santos Boutique')
            ->set('business_type', 'Retail')
            ->set('preferred_stall_id', $stall->id)
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('stall_applications', [
            'applicant_name' => 'Maria Santos',
            'business_name' => 'Santos Boutique',
            'status' => 'pending',
        ]);
    }
}
