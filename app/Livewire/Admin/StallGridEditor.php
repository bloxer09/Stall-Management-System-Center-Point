<?php

namespace App\Livewire\Admin;

use App\Models\Stall;
use App\Models\StallLocation;
use Livewire\Component;

class StallGridEditor extends Component
{
    public $selectedLocationId = null;
    public $locations = [];
    public $gridRows = 6;
    public $gridCols = 8;
    public $unassignedStalls = [];
    public $selectedStallId = null;

    public function mount()
    {
        $this->locations = StallLocation::orderBy('sort_order')->get();
        if ($this->locations->isNotEmpty()) {
            $this->selectedLocationId = $this->locations->first()->id;
            $this->updatedSelectedLocationId($this->selectedLocationId);
        }
    }

    public function updatedSelectedLocationId($value)
    {
        $loc = StallLocation::find($value);
        if ($loc) {
            $this->gridRows = $loc->grid_rows;
            $this->gridCols = $loc->grid_cols;
        }

        $this->unassignedStalls = Stall::where(function ($query) use ($value) {
            $query->whereNull('location_id')->orWhere('location_id', $value);
        })->where(function ($query) {
            $query->whereNull('grid_row')->orWhereNull('grid_col');
        })->get();
    }

    /**
     * Click-to-place: put the stall chosen from the unassigned list into a cell.
     */
    public function placeStallOnGrid($row, $col)
    {
        if (!$this->selectedStallId) return;

        if ($this->moveStall((int) $this->selectedStallId, (int) $row, (int) $col)) {
            $this->selectedStallId = null;
        }
    }

    /**
     * Drag-and-drop: move an unplaced or already placed stall into an empty cell of this floor.
     */
    public function moveStall(mixed $stallId, mixed $row, mixed $col): bool
    {
        // Values come straight from the browser's drop event; anything that isn't a whole number is ignored.
        foreach ([$stallId, $row, $col] as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                return false;
            }
        }
        [$stallId, $row, $col] = [(int) $stallId, (int) $row, (int) $col];

        $stall = Stall::find($stallId);
        if (!$stall) {
            return false;
        }

        if ($error = $this->validatePlacement($stall, $row, $col)) {
            $this->dispatch('notify', type: 'error', message: $error);
            return false;
        }

        $stall->update([
            'location_id' => $this->selectedLocationId,
            'grid_row' => $row,
            'grid_col' => $col,
        ]);

        $this->dispatch('notify', type: 'success', message: "Placed Stall {$stall->stall_number} at Row {$row}, Col {$col}");
        $this->updatedSelectedLocationId($this->selectedLocationId);

        return true;
    }

    /**
     * Server-side checks shared by click and drag placement; the browser can send any numbers.
     */
    private function validatePlacement(Stall $stall, int $row, int $col): ?string
    {
        $location = StallLocation::find($this->selectedLocationId);
        if (!$location) {
            return 'Choose a floor first.';
        }

        if ($row < 1 || $row > (int) $location->grid_rows || $col < 1 || $col > (int) $location->grid_cols) {
            return "That cell is outside this floor's grid.";
        }

        if ($stall->location_id !== null && (int) $stall->location_id !== (int) $location->id) {
            return 'That stall belongs to another floor.';
        }

        $occupied = Stall::where('location_id', $location->id)
            ->where('grid_row', $row)
            ->where('grid_col', $col)
            ->where('id', '!=', $stall->id)
            ->exists();

        return $occupied ? "Cell (Row {$row}, Col {$col}) is already occupied by another stall." : null;
    }

    public function removeStallFromGrid($stallId)
    {
        $stall = Stall::find($stallId);
        if ($stall) {
            $stall->update([
                'grid_row' => null,
                'grid_col' => null,
            ]);
            $this->dispatch('notify', type: 'success', message: "Unassigned Stall {$stall->stall_number} from grid");
            $this->updatedSelectedLocationId($this->selectedLocationId);
        }
    }

    public function render()
    {
        $gridStalls = [];
        if ($this->selectedLocationId) {
            $stalls = Stall::where('location_id', $this->selectedLocationId)
                ->whereNotNull('grid_row')
                ->whereNotNull('grid_col')
                ->get();

            foreach ($stalls as $s) {
                $gridStalls["{$s->grid_row}_{$s->grid_col}"] = $s;
            }
        }

        return view('livewire.admin.stall-grid-editor', [
            'gridStalls' => $gridStalls,
        ]);
    }
}
