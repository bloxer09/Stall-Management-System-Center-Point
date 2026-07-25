<?php

namespace App\Livewire\User;

use App\Models\StallMapping;
use Livewire\Component;

class MyLeases extends Component
{
    public $showRenewalModal = false;
    public $selectedMappingId = null;
    public $requested_end_date = '';

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
        $leases = StallMapping::with(['stall.location'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('livewire.user.my-leases', [
            'leases' => $leases,
        ]);
    }
}
