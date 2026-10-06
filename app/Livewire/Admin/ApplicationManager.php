<?php

namespace App\Livewire\Admin;

use App\Models\StallApplication;
use App\Services\StallApplicationService;
use DomainException;
use Livewire\Component;
use Livewire\WithPagination;

class ApplicationManager extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';
    public string $search = '';
    public ?int $selectedId = null;
    public string $rejectionReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->resetValidation();
        $this->rejectionReason = '';
        $this->selectedId = StallApplication::whereKey($id)->value('id');
    }

    public function closeDetail(): void
    {
        $this->selectedId = null;
        $this->rejectionReason = '';
    }

    public function approve(StallApplicationService $applications): void
    {
        $application = StallApplication::find($this->selectedId);
        if (!$application) {
            return;
        }

        try {
            $result = $applications->approve($application, auth()->user());
        } catch (DomainException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
            return;
        }

        $message = $result['created']
            ? 'Approved. A tenant account was created and a set-password link was emailed.'
            : 'Approved and linked to the applicant\'s existing account.';
        $this->dispatch('notify', type: 'success', message: $message);

        if (!$result['emailSent']) {
            $this->dispatch('notify', type: 'warning', message: 'The approval was saved, but the email to the applicant could not be sent.');
        }
    }

    public function reject(StallApplicationService $applications): void
    {
        $application = StallApplication::find($this->selectedId);
        if (!$application) {
            return;
        }

        $this->validate(['rejectionReason' => 'required|string|max:500']);

        try {
            $applications->reject($application, auth()->user(), trim($this->rejectionReason));
        } catch (DomainException $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
            return;
        }

        $this->dispatch('notify', type: 'success', message: 'Application rejected and the applicant was notified.');
        $this->closeDetail();
    }

    public function render()
    {
        $query = StallApplication::with('preferredStall.location')->latest();

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('applicant_name', 'like', $term)
                    ->orWhere('applicant_email', 'like', $term)
                    ->orWhere('business_name', 'like', $term)
                    ->orWhereHas('preferredStall', fn ($s) => $s->where('stall_number', 'like', $term));
            });
        }

        return view('livewire.admin.application-manager', [
            'applications' => $query->paginate(10),
            'selected' => $this->selectedId
                ? StallApplication::with(['preferredStall.location', 'reviewer', 'user'])->find($this->selectedId)
                : null,
        ]);
    }
}
