<?php

namespace Tests\Feature;

use App\Livewire\User\TicketManager;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TicketStallScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantA;
    private Stall $stallA;
    private Stall $stallB;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'admin']);
        $this->tenantA = User::factory()->create(['role' => 'user']);
        $tenantB = User::factory()->create(['role' => 'user']);
        $location = StallLocation::create(['name' => 'Floor 1', 'sort_order' => 1]);
        $this->stallA = Stall::create(['stall_number' => 'S-1', 'location_id' => $location->id, 'price' => 1000, 'status' => 'occupied']);
        $this->stallB = Stall::create(['stall_number' => 'S-2', 'location_id' => $location->id, 'price' => 1000, 'status' => 'occupied']);

        foreach ([[$this->tenantA, $this->stallA], [$tenantB, $this->stallB]] as [$tenant, $stall]) {
            StallMapping::create([
                'user_id' => $tenant->id,
                'stall_id' => $stall->id,
                'start_date' => now()->subMonth(),
                'end_date' => now()->addYear(),
                'is_active' => true,
                'payment_status' => 'good_standing',
            ]);
        }
    }

    public function test_tenant_cannot_open_ticket_on_stall_they_do_not_lease(): void
    {
        Livewire::actingAs($this->tenantA)
            ->test(TicketManager::class)
            ->set('stall_id', $this->stallB->id)
            ->set('issue', 'Leaking roof near the entrance')
            ->call('createTicket')
            ->assertHasErrors('stall_id');

        $this->assertDatabaseMissing('tickets', ['stall_id' => $this->stallB->id]);
    }

    public function test_tenant_can_open_ticket_on_own_leased_stall(): void
    {
        Livewire::actingAs($this->tenantA)
            ->test(TicketManager::class)
            ->set('stall_id', $this->stallA->id)
            ->set('issue', 'Leaking roof near the entrance')
            ->call('createTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', ['user_id' => $this->tenantA->id, 'stall_id' => $this->stallA->id]);
    }
}
