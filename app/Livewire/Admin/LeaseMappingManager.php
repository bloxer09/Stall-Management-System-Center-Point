<?php

namespace App\Livewire\Admin;

use App\Models\Stall;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\FileUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeaseMappingManager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $statusFilter = '';
    public $showModal = false;

    // Lease Detail & Actions Modal
    public $selectedLease = null;
    public $showDetailModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public $mappingId = null;
    public $user_id = '';
    public $stall_id = '';
    public $start_date = '';
    public $end_date = '';
    public $contract_file = null;

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'stall_id' => 'required|exists:stalls,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'contract_file' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
    ];

    public function openDetailModal($id)
    {
        $this->selectedLease = StallMapping::with(['user', 'stall.location'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLease = null;
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['mappingId', 'user_id', 'stall_id', 'start_date', 'end_date', 'contract_file']);
        $this->showDetailModal = false;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveMapping(\App\Services\LeaseService $leaseService)
    {
        $this->validate();

        try {
            $leaseService->createLease(
                (int) $this->user_id,
                (int) $this->stall_id,
                $this->start_date,
                $this->end_date,
                $this->contract_file
            );

            $this->dispatch('notify', type: 'success', message: 'Lease assigned successfully!');
            $this->closeModal();
        } catch (\InvalidArgumentException $e) {
            $this->addError('stall_id', $e->getMessage());
        }
    }

    public function approveRenewal($mappingId, \App\Services\LeaseService $leaseService)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        $leaseService->approveRenewal($mapping);
        $this->dispatch('notify', type: 'success', message: 'Renewal request approved!');
        $this->closeDetailModal();
    }

    public function rejectRenewal($mappingId, \App\Services\LeaseService $leaseService)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        $leaseService->rejectRenewal($mapping);
        $this->dispatch('notify', type: 'success', message: 'Renewal request rejected.');
        $this->closeDetailModal();
    }

    public function terminateLease($mappingId, \App\Services\LeaseService $leaseService)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        $leaseService->terminateLease($mapping);
        $this->dispatch('notify', type: 'success', message: 'Lease terminated successfully.');
        $this->closeDetailModal();
    }

    public function render()
    {
        $query = StallMapping::with(['user', 'stall.location']);

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })->orWhereHas('stall', function ($q) {
                $q->where('stall_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('is_active', false);
            } elseif ($this->statusFilter === 'requested') {
                $query->where('renewal_status', 'requested');
            }
        }

        return view('livewire.admin.lease-mapping-manager', [
            'mappings' => $query->latest()->paginate(10),
            'users' => User::where('role', 'user')->orderBy('name')->get(),
            'availableStalls' => Stall::where('status', 'available')->orderBy('stall_number')->get(),
        ]);
    }
}
