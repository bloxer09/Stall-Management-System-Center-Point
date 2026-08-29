<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\StallLocation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FloorManager extends Component
{
    use WithPagination;

    public $search = '';

    // Floor Form Fields
    public $floorId = null;
    public $name = '';
    public $description = '';
    public $sort_order = 1;
    public $grid_rows = 6;
    public $grid_cols = 8;
    public $showModal = false;

    // Floor Detail & Manage Modal
    public $selectedFloor = null;
    public $showDetailModal = false;

    protected $rules = [
        'name' => 'required|string|max:100',
        'description' => 'nullable|string|max:255',
        'sort_order' => 'required|integer|min:1',
        'grid_rows' => 'required|integer|min:1|max:20',
        'grid_cols' => 'required|integer|min:1|max:20',
    ];

    public function openDetailModal($id)
    {
        $this->selectedFloor = StallLocation::withCount('stalls')->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedFloor = null;
    }

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->showDetailModal = false;

        if ($id) {
            $floor = StallLocation::findOrFail($id);
            $this->floorId = $floor->id;
            $this->name = $floor->name;
            $this->description = $floor->description;
            $this->sort_order = $floor->sort_order;
            $this->grid_rows = $floor->grid_rows;
            $this->grid_cols = $floor->grid_cols;
        } else {
            $this->reset(['floorId', 'name', 'description']);
            $this->sort_order = StallLocation::count() + 1;
            $this->grid_rows = 6;
            $this->grid_cols = 8;
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveFloor()
    {
        $this->validate();

        $floor = StallLocation::updateOrCreate(
            ['id' => $this->floorId],
            [
                'name' => $this->name,
                'description' => $this->description,
                'sort_order' => $this->sort_order,
                'grid_rows' => $this->grid_rows,
                'grid_cols' => $this->grid_cols,
            ]
        );

        AuditLog::log(
            Auth::id(),
            $this->floorId ? "Updated floor location '{$this->name}'" : "Created new floor location '{$this->name}'",
            'StallLocation',
            $floor->id
        );

        $this->dispatch('notify', type: 'success', message: $this->floorId ? 'Floor level updated successfully!' : "Floor level '{$this->name}' created successfully!");
        $this->closeModal();
    }

    public function deleteFloor($id)
    {
        $floor = StallLocation::findOrFail($id);
        $name = $floor->name;
        $floor->delete();

        $this->closeDetailModal();
        AuditLog::log(Auth::id(), "Deleted floor level '{$name}'", 'StallLocation', $id);
        $this->dispatch('notify', type: 'success', message: "Floor level '{$name}' deleted successfully!");
    }

    public function render()
    {
        $query = StallLocation::withCount('stalls');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return view('livewire.admin.floor-manager', [
            'floors' => $query->orderBy('sort_order')->paginate(10),
        ]);
    }
}
