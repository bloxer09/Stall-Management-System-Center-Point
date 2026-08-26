<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Support\Facades\Auth;
use App\Services\FileUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class StallManager extends Component
{
    use WithFileUploads, WithPagination;

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
    public $image = null;
    public $existing_image = null;
    public $showModal = false;

    protected $rules = [
        'stall_number' => 'required|string|max:50',
        'location_id' => 'nullable|exists:stall_locations,id',
        'price' => 'required|numeric|min:0',
        'status' => 'required|in:available,occupied,maintenance',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:5120',
    ];

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['image', 'existing_image']);
        if ($id) {
            $stall = Stall::findOrFail($id);
            $this->stallId = $stall->id;
            $this->stall_number = $stall->stall_number;
            $this->location_id = $stall->location_id;
            $this->price = $stall->price;
            $this->status = $stall->status;
            $this->description = $stall->description;
            $this->existing_image = $stall->image;
        } else {
            $this->reset(['stallId', 'stall_number', 'location_id', 'price', 'status', 'description', 'image', 'existing_image']);
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['image', 'existing_image']);
    }

    public function saveStall(FileUploadService $uploader)
    {
        $this->validate();

        $imagePath = $this->existing_image;
        if ($this->image) {
            $imagePath = $uploader->uploadStallImage($this->image);
        }

        $stall = Stall::updateOrCreate(
            ['id' => $this->stallId],
            [
                'stall_number' => $this->stall_number,
                'location_id' => $this->location_id ?: null,
                'price' => $this->price,
                'status' => $this->status,
                'description' => $this->description,
                'image' => $imagePath,
            ]
        );

        AuditLog::record(
            $this->stallId ? "Updated stall {$this->stall_number}" : "Created new stall {$this->stall_number}",
            'Stall',
            $stall->id,
            null,
            Auth::id()
        );

        $this->dispatch('notify', type: 'success', message: $this->stallId ? 'Stall updated successfully!' : 'Stall created successfully!');
        $this->closeModal();
    }

    public function deleteStall($id)
    {
        $stall = Stall::findOrFail($id);
        $number = $stall->stall_number;
        $stall->delete();

        AuditLog::record("Deleted stall {$number}", 'Stall', $id, null, Auth::id());
        $this->dispatch('notify', type: 'success', message: 'Stall deleted successfully!');
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
