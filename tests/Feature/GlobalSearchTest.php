<?php

namespace Tests\Feature;

use App\Livewire\Admin\GlobalSearch;
use App\Livewire\Admin\StallManager;
use App\Livewire\Admin\TicketCenter;
use App\Models\Invoice;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tenant;
    private Stall $stall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->tenant = User::factory()->create(['role' => 'user', 'name' => 'Lorna Diaz', 'phone' => '09998887777']);
        $floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1]);
        $this->stall = Stall::create(['stall_number' => 'S-101', 'location_id' => $floor->id, 'price' => 4500, 'status' => 'occupied']);
    }

    private function search(string $query)
    {
        return Livewire::actingAs($this->admin)->test(GlobalSearch::class)->set('query', $query);
    }

    public function test_short_query_returns_nothing(): void
    {
        $results = $this->search('S')->instance()->results();

        $this->assertTrue(collect($results)->every->isEmpty());
    }

    public function test_groups_results(): void
    {
        Invoice::create(['invoice_number' => 'INV-2026-0042', 'user_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'total_amount' => 4500, 'status' => 'pending', 'due_date' => now()]);
        $ticket = Ticket::create(['user_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'issue' => 'Leaking faucet near the sink', 'status' => 'open']);

        $this->search('S-10')->assertSee('S-101');
        $this->search('0999888')->assertSee('Lorna Diaz');
        $this->search('INV-2026-0042')->assertSee('INV-2026-0042');
        $this->search('faucet')->assertSee('Leaking faucet');
        $this->search((string) $ticket->id)->assertSee('Ticket #' . $ticket->id);
    }

    public function test_admins_are_not_listed_as_tenants(): void
    {
        $this->search(substr($this->admin->email, 0, 6))->assertDontSee($this->admin->email);
    }

    public function test_max_five_per_group(): void
    {
        for ($i = 2; $i <= 8; $i++) {
            Stall::create(['stall_number' => 'S-10' . $i, 'location_id' => $this->stall->location_id, 'price' => 1, 'status' => 'available']);
        }

        $this->assertCount(5, $this->search('S-10')->instance()->results()['stalls']);
    }

    public function test_like_wildcards_are_literal(): void
    {
        $this->assertTrue($this->search('%%')->instance()->results()['stalls']->isEmpty());
        $this->assertTrue($this->search('__')->instance()->results()['stalls']->isEmpty());
    }

    public function test_non_admin_forbidden(): void
    {
        Livewire::actingAs($this->tenant)->test(GlobalSearch::class)->assertForbidden();
    }

    public function test_target_pages_accept_query(): void
    {
        $ticket = Ticket::create(['user_id' => $this->tenant->id, 'stall_id' => $this->stall->id, 'issue' => 'Broken light', 'status' => 'open']);

        Livewire::actingAs($this->admin)->withQueryParams(['search' => 'S-101'])->test(StallManager::class)->assertSet('search', 'S-101');
        Livewire::actingAs($this->admin)->withQueryParams(['ticket' => $ticket->id])->test(TicketCenter::class)
            ->assertSet('selectedTicketId', $ticket->id)
            ->assertSet('showThreadModal', true);
    }

    public function test_search_box_rendered_for_admins_only(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertSeeLivewire(GlobalSearch::class);
        $this->actingAs($this->tenant)->get('/user/dashboard')->assertDontSeeLivewire(GlobalSearch::class);
    }
}
