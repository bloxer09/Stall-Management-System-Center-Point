<?php

namespace Tests\Feature;

use App\Livewire\Portal\StallBrowser;
use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StallBrowserTest extends TestCase
{
    use RefreshDatabase;

    private StallLocation $floor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->floor = StallLocation::create(['name' => 'Ground Floor', 'description' => 'Retail zone', 'sort_order' => 1, 'grid_rows' => 2, 'grid_cols' => 2]);
    }

    private function stall(string $number, string $status, ?int $row = null, ?int $col = null): Stall
    {
        return Stall::create([
            'stall_number' => $number,
            'location_id' => $this->floor->id,
            'price' => 4500,
            'status' => $status,
            'grid_row' => $row,
            'grid_col' => $col,
        ]);
    }

    public function test_guest_can_view_stall_browser(): void
    {
        $this->stall('S-101', 'available', 1, 1);

        $this->get('/stalls')->assertOk()->assertSee('S-101');
    }

    public function test_only_available_stall_offers_apply(): void
    {
        $available = $this->stall('S-101', 'available', 1, 1);
        $occupied = $this->stall('S-102', 'occupied', 1, 2);

        Livewire::test(StallBrowser::class)
            ->call('selectStall', $available->id)
            ->assertSee('Apply for this stall');

        Livewire::test(StallBrowser::class)
            ->call('selectStall', $occupied->id)
            ->assertDontSee('Apply for this stall')
            ->assertSee('Occupied');
    }

    public function test_stalls_without_or_outside_grid_position_are_listed_not_crashing(): void
    {
        $this->stall('S-NOPOS', 'available');
        $this->stall('S-OUTSIDE', 'available', 5, 1);

        $this->get('/stalls')
            ->assertOk()
            ->assertSee('Other stalls on this floor')
            ->assertSee('S-NOPOS')
            ->assertSee('S-OUTSIDE');
    }

    public function test_home_shows_real_floor_counts(): void
    {
        $this->stall('S-1', 'available', 1, 1);
        $this->stall('S-2', 'available', 1, 2);
        $this->stall('S-3', 'occupied', 2, 1);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ground Floor')
            ->assertSee('2 of 3 available')
            ->assertDontSee('Ground Floor Arcade')
            ->assertSee(route('stalls.browse'));
    }
}
