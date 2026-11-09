<?php

namespace Tests\Feature;

use App\Livewire\Admin\StallGridEditor;
use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StallGridEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StallLocation $floor;
    private StallLocation $otherFloor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->floor = StallLocation::create(['name' => 'Ground Floor', 'sort_order' => 1, 'grid_rows' => 3, 'grid_cols' => 4]);
        $this->otherFloor = StallLocation::create(['name' => 'Second Floor', 'sort_order' => 2, 'grid_rows' => 3, 'grid_cols' => 4]);
    }

    private function stall(string $number, ?StallLocation $floor, ?int $row = null, ?int $col = null): Stall
    {
        return Stall::create(['stall_number' => $number, 'location_id' => $floor?->id, 'price' => 1000, 'status' => 'available', 'grid_row' => $row, 'grid_col' => $col]);
    }

    private function editor()
    {
        return Livewire::actingAs($this->admin)->test(StallGridEditor::class);
    }

    public function test_moves_placed_stall_to_empty_cell(): void
    {
        $stall = $this->stall('G-1', $this->floor, 1, 1);

        $this->editor()->call('moveStall', $stall->id, 2, 3);

        $this->assertSame([2, 3], [$stall->fresh()->grid_row, $stall->fresh()->grid_col]);
    }

    public function test_drops_unplaced_stall_onto_grid(): void
    {
        $stall = $this->stall('G-2', null);

        $this->editor()->call('moveStall', $stall->id, 1, 2);

        $stall->refresh();
        $this->assertSame($this->floor->id, $stall->location_id);
        $this->assertSame([1, 2], [$stall->grid_row, $stall->grid_col]);
    }

    public function test_occupied_cell_is_rejected(): void
    {
        $this->stall('G-3', $this->floor, 1, 1);
        $mover = $this->stall('G-4', $this->floor, 2, 2);

        $this->editor()->call('moveStall', $mover->id, 1, 1)
            ->assertDispatched('notify', type: 'error');

        $this->assertSame([2, 2], [$mover->fresh()->grid_row, $mover->fresh()->grid_col]);
    }

    public function test_cell_outside_grid_is_rejected(): void
    {
        $stall = $this->stall('G-5', $this->floor, 1, 1);

        $this->editor()->call('moveStall', $stall->id, 4, 1)->assertDispatched('notify', type: 'error', message: "That cell is outside this floor's grid.");
        $this->editor()->call('moveStall', $stall->id, 1, 0)->assertDispatched('notify', type: 'error');

        $this->assertSame([1, 1], [$stall->fresh()->grid_row, $stall->fresh()->grid_col]);
    }

    public function test_stall_from_another_floor_is_rejected(): void
    {
        $stall = $this->stall('S-1', $this->otherFloor, 1, 1);

        $this->editor()->call('moveStall', $stall->id, 2, 2)->assertDispatched('notify', type: 'error', message: 'That stall belongs to another floor.');

        $stall->refresh();
        $this->assertSame($this->otherFloor->id, $stall->location_id);
        $this->assertSame([1, 1], [$stall->grid_row, $stall->grid_col]);
    }

    public function test_click_to_place_also_checks_bounds(): void
    {
        $stall = $this->stall('G-6', null);

        $this->editor()->set('selectedStallId', $stall->id)->call('placeStallOnGrid', 9, 9)->assertDispatched('notify', type: 'error');

        $this->assertNull($stall->fresh()->grid_row);
    }

    public function test_grid_cells_are_drag_targets(): void
    {
        $this->stall('G-7', $this->floor, 1, 1);

        $html = $this->editor()->html();

        $this->assertStringContainsString('draggable="true"', $html);
        $this->assertStringContainsString('$wire.moveStall(', $html);
    }
}
