<?php

namespace App\Livewire\User;

use App\Models\StallMapping;
use Livewire\Component;
use Livewire\WithPagination;

class MyLeases extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $showRenewalModal = false;
    public $selectedMappingId = null;
    public $requested_end_date = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openRenewalModal($mappingId)
    {
        $this->resetValidation();
        $this->selectedMappingId = $mappingId;
        $mapping = StallMapping::findOrFail($mappingId);
        $this->requested_end_date = $mapping->end_date->addMonth()->format('Y-m-d');
        $this->showRenewalModal = true;
    }

    public function closeRenewalModal()
    {
        $this->showRenewalModal = false;
    }

    public function submitRenewalRequest()
    {
        $this->validate([
            'requested_end_date' => 'required|date|after:today',
        ]);

        $mapping = StallMapping::where('user_id', auth()->id())->findOrFail($this->selectedMappingId);
        $mapping->update([
            'renewal_status' => 'requested',
            'requested_end_date' => $this->requested_end_date,
        ]);

        session()->flash('message', 'Lease renewal request submitted to administrator.');
        $this->closeRenewalModal();
    }

    public function render()
    {
        $query = StallMapping::with(['stall.location'])
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->whereHas('stall', function ($q) {
                $q->where('stall_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'requested') {
            $query->where('renewal_status', 'requested');
        } elseif ($this->statusFilter === 'terminated') {
            $query->where('is_active', false);
        }

        return view('livewire.user.my-leases', [
            'leases' => $query->latest()->paginate(10),
            'activeCount' => StallMapping::where('user_id', auth()->id())->where('is_active', true)->count(),
        ]);
    }
}
