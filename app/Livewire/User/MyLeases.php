<?php

namespace App\Livewire\User;

use App\Models\AuditLog;
use App\Models\StallMapping;
use App\Services\FileUploadService;
use App\Services\LeaseService;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MyLeases extends Component
{
    use WithFileUploads, WithPagination;

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

    // Lease Renewal Request Modal State
    public ?int $renewalLeaseId = null;
    public string $renewalOption = '6m';
    public string $renewalCustomDate = '';
    public string $renewalNote = '';

    public function openRenewal(int $id, LeaseService $leases): void
    {
        $this->resetValidation();
        $lease = StallMapping::where('user_id', auth()->id())->find($id);

        $this->renewalLeaseId = $lease && $leases->isRenewalEligible($lease) ? $lease->id : null;
        $this->renewalOption = '6m';
        $this->renewalCustomDate = '';
        $this->renewalNote = '';
    }

    public function closeRenewal(): void
    {
        $this->renewalLeaseId = null;
    }

    public function submitRenewal(LeaseService $leases): void
    {
        $lease = $this->renewalLeaseId
            ? StallMapping::where('user_id', auth()->id())->find($this->renewalLeaseId)
            : null;
        if (!$lease) {
            return;
        }

        if ($this->renewalOption === 'custom') {
            $this->validate(['renewalCustomDate' => ['required', 'date']], [], ['renewalCustomDate' => 'new end date']);
            $requestedEnd = Carbon::parse($this->renewalCustomDate);
        } else {
            $requestedEnd = $this->renewalOption === '1y'
                ? $lease->end_date->copy()->addYear()
                : $lease->end_date->copy()->addMonths(6);
        }

        try {
            $leases->requestRenewal($lease, auth()->user(), $requestedEnd, mb_substr($this->renewalNote, 0, 500));
        } catch (InvalidArgumentException $e) {
            $this->addError('renewal', $e->getMessage());
            return;
        }

        $this->renewalLeaseId = null;
        $this->dispatch('notify', type: 'success', message: 'Renewal request sent. The office will review it.');
    }

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
            'renewalLease' => $this->renewalLeaseId
                ? StallMapping::with('stall')->where('user_id', auth()->id())->find($this->renewalLeaseId)
                : null,
        ]);
    }
}
