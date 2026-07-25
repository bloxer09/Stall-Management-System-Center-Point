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

    public function placeStallOnGrid($row, $col)
    {
        if (!$this->selectedStallId) return;

        $stall = Stall::find($this->selectedStallId);
        if ($stall) {
            $stall->update([
                'location_id' => $this->selectedLocationId,
                'grid_row' => $row,
                'grid_col' => $col,
            ]);

            session()->flash('message', "Assigned Stall {$stall->stall_number} to Row {$row}, Col {$col}");
            $this->selectedStallId = null;
            $this->updatedSelectedLocationId($this->selectedLocationId);
        }
    }

    public function removeStallFromGrid($stallId)
    {
        $stall = Stall::find($stallId);
        if ($stall) {
            $stall->update([
                'grid_row' => null,
                'grid_col' => null,
            ]);
            session()->flash('message', "Unassigned Stall {$stall->stall_number} from grid");
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
