<?php

namespace App\Livewire\User;

use App\Models\AuditLog;
use App\Models\StallMapping;
use App\Livewire\Concerns\RequestsLeaseRenewal;
use App\Services\FileUploadService;
use App\Services\LeaseService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MyLeases extends Component
{
    use RequestsLeaseRenewal, WithFileUploads, WithPagination;

    public $search = '';
    public $statusFilter = '';

    // Lease Detail Modal State
    public $selectedLease = null;
    public $showDetailModal = false;

    // Stall Photo Upload Modal State
    public $showImageModal = false;
    public $selectedStallId = null;
    public $newStallImage = null;
    public $existingStallImage = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openDetailModal($id)
    {
        $this->selectedLease = StallMapping::with(['stall.location', 'initialInvoice'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLease = null;
    }

    public function openImageModal($stallId)
    {
        $this->resetValidation();
        $mapping = StallMapping::with('stall')
            ->where('user_id', auth()->id())
            ->where('stall_id', $stallId)
            ->where('is_active', true)
            ->first();

        if (!$mapping) return;

        $this->selectedStallId = $stallId;
        $this->existingStallImage = $mapping->stall->image;
        $this->newStallImage = null;
        $this->showDetailModal = false;
        $this->showImageModal = true;
    }

    public function closeImageModal()
    {
        $this->showImageModal = false;
        $this->selectedStallId = null;
        $this->newStallImage = null;
        $this->existingStallImage = null;
    }

    public function uploadStallPhoto(FileUploadService $uploader)
    {
        if (!$this->selectedStallId) return;

        $mapping = StallMapping::with('stall')
            ->where('user_id', auth()->id())
            ->where('stall_id', $this->selectedStallId)
            ->where('is_active', true)
            ->first();

        if (!$mapping) return;

        $this->validate([
            'newStallImage' => 'required|image|max:5120',
        ]);

        $imagePath = $uploader->uploadStallImage($this->newStallImage);
        $mapping->stall->update(['image' => $imagePath]);

        AuditLog::log(
            auth()->id(),
            "Tenant updated photo for Stall {$mapping->stall->stall_number}",
            'Stall',
            $mapping->stall_id
        );

        $this->dispatch('notify', type: 'success', message: 'Stall storefront photo updated successfully!');
        $this->closeImageModal();
    }

    public function render()
    {
        $query = StallMapping::with(['stall.location', 'initialInvoice'])
            ->where('user_id', auth()->id());

        if ($this->search) {
            $query->whereHas('stall', function ($q) {
                $q->where('stall_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true);
            } elseif ($this->statusFilter === 'pending') {
                $query->where('payment_status', 'pending_initial');
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('is_active', false)->where('payment_status', '!=', 'pending_initial');
            }
        }

        $leases = $query->latest()->paginate(8);
        $leaseService = app(LeaseService::class);

        return view('livewire.user.my-leases', [
            'leases' => $leases,
            'renewableIds' => $leases->getCollection()
                ->filter(fn (StallMapping $lease) => $leaseService->isRenewalEligible($lease))
                ->pluck('id')
                ->all(),
            'renewalLease' => $this->renewalLease(),
        ]);
    }
}
