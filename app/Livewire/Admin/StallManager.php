<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class StallManager extends Component
{
    use WithPagination;

    public $search = '';
    public $locationFilter = '';
    public $statusFilter = '';

    // Stall Form Fields
    public $stallId = null;
    public $stall_number = '';
    public $location_id = '';
    public $price = '';
    public $status = 'available';
    public $description = '';
    public $showModal = false;

    // Floor Location Form Fields
    public $showLocationModal = false;
    public $editingLocationId = null;
    public $location_name = '';
    public $location_description = '';
    public $location_sort_order = 1;
    public $location_grid_rows = 6;
    public $location_grid_cols = 8;

    protected $rules = [
        'stall_number' => 'required|string|max:50',
        'location_id' => 'nullable|exists:stall_locations,id',
        'price' => 'required|numeric|min:0',
        'status' => 'required|in:available,occupied,maintenance',
        'description' => 'nullable|string',
    ];

    public function openModal($id = null)
    {
        $this->resetValidation();
        if ($id) {
            $stall = Stall::findOrFail($id);
            $this->stallId = $stall->id;
            $this->stall_number = $stall->stall_number;
            $this->location_id = $stall->location_id;
            $this->price = $stall->price;
            $this->status = $stall->status;
            $this->description = $stall->description;
        } else {
            $this->reset(['stallId', 'stall_number', 'location_id', 'price', 'status', 'description']);
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveStall()
    {
        $this->validate();

        Stall::updateOrCreate(
            ['id' => $this->stallId],
            [
                'stall_number' => $this->stall_number,
                'location_id' => $this->location_id ?: null,
                'price' => $this->price,
                'status' => $this->status,
                'description' => $this->description,
            ]
        );

        AuditLog::log(
            Auth::id(),
            $this->stallId ? "Updated stall {$this->stall_number}" : "Created new stall {$this->stall_number}",
            'Stall',
            $this->stallId ?: 1
        );

        session()->flash('message', $this->stallId ? 'Stall updated successfully!' : 'Stall created successfully!');
        $this->closeModal();
    }

    public function deleteStall($id)
    {
        $stall = Stall::findOrFail($id);
        $number = $stall->stall_number;
        $stall->delete();

        AuditLog::log(Auth::id(), "Deleted stall {$number}", 'Stall', $id);
        session()->flash('message', 'Stall deleted successfully!');
    }

    // Floor Location Management Methods
    public function openLocationModal($id = null)
    {
        $this->resetValidation();
        if ($id) {
            $location = StallLocation::findOrFail($id);
            $this->editingLocationId = $location->id;
            $this->location_name = $location->name;
            $this->location_description = $location->description;
            $this->location_sort_order = $location->sort_order;
            $this->location_grid_rows = $location->grid_rows;
            $this->location_grid_cols = $location->grid_cols;
        } else {
            $this->reset(['editingLocationId', 'location_name', 'location_description']);
            $this->location_sort_order = StallLocation::count() + 1;
            $this->location_grid_rows = 6;
            $this->location_grid_cols = 8;
        }
        $this->showLocationModal = true;
    }

    public function closeLocationModal()
    {
        $this->showLocationModal = false;
    }

    public function saveLocation()
    {
        $this->validate([
            'location_name' => 'required|string|max:100',
            'location_description' => 'nullable|string|max:255',
            'location_sort_order' => 'required|integer|min:1',
            'location_grid_rows' => 'required|integer|min:1|max:20',
            'location_grid_cols' => 'required|integer|min:1|max:20',
        ]);

        $loc = StallLocation::updateOrCreate(
            ['id' => $this->editingLocationId],
            [
                'name' => $this->location_name,
                'description' => $this->location_description,
                'sort_order' => $this->location_sort_order,
                'grid_rows' => $this->location_grid_rows,
                'grid_cols' => $this->location_grid_cols,
            ]
        );

        AuditLog::log(
            Auth::id(),
            $this->editingLocationId ? "Updated floor location '{$this->location_name}'" : "Created new floor location '{$this->location_name}'",
            'StallLocation',
            $loc->id
        );

        session()->flash('message', $this->editingLocationId ? 'Floor location updated successfully!' : "Floor location '{$this->location_name}' added successfully!");
        $this->closeLocationModal();
    }

    public function deleteLocation($id)
    {
        $loc = StallLocation::findOrFail($id);
        $name = $loc->name;
        $loc->delete();

        AuditLog::log(Auth::id(), "Deleted floor location '{$name}'", 'StallLocation', $id);
        session()->flash('message', "Floor location '{$name}' deleted successfully!");
    }

    public function render()
    {
        $query = Stall::with('location');

        if ($this->search) {
            $query->where('stall_number', 'like', "%{$this->search}%");
        }
        if ($this->locationFilter) {
            $query->where('location_id', $this->locationFilter);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.stall-manager', [
            'stalls' => $query->paginate(10),
            'locations' => StallLocation::orderBy('sort_order')->get(),
        ]);
    }
}
