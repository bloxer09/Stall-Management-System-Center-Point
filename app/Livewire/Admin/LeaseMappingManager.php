<?php

namespace App\Livewire\Admin;

use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\InvoiceService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeaseMappingManager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $locationFilter = '';
    public $statusFilter = '';
    public $showModal = false;

    // Lease Detail & Actions Modal
    public $selectedLease = null;
    public $showDetailModal = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
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
        $this->selectedLease = StallMapping::with(['user', 'stall.location', 'initialInvoice'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLease = null;
    }

    public function mount(): void
    {
        // "Assign stall" from an approved application arrives with ?user=&stall= to pre-fill the form.
        $userId = (int) request()->query('user');
        $stallId = (int) request()->query('stall');

        if ($userId && User::whereKey($userId)->where('role', 'user')->exists()) {
            $this->openModal();
            $this->user_id = $userId;
            if ($stallId && Stall::whereKey($stallId)->where('status', 'available')->exists()) {
                $this->stall_id = $stallId;
            }
        }
    }

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['mappingId', 'user_id', 'stall_id', 'start_date', 'end_date', 'contract_file']);
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addYear()->format('Y-m-d');
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

            $this->dispatch('notify', type: 'success', message: 'Stall assigned! Initial invoice generated. Stall is reserved awaiting tenant payment.');
            $this->closeModal();
        } catch (\InvalidArgumentException $e) {
            $this->addError('stall_id', $e->getMessage());
        }
    }

    public function recordInitialCashPayment($mappingId, InvoiceService $invoiceService)
    {
        $mapping = StallMapping::with('initialInvoice')->findOrFail($mappingId);

        if ($mapping->initialInvoice && $mapping->initialInvoice->status === 'pending') {
            $invoiceService->markAsPaid($mapping->initialInvoice, 'office_cash', 'OTC-' . time());
            $this->dispatch('notify', type: 'success', message: 'Office cash payment recorded! Lease is now Active in Good Standing and Stall is Occupied.');
        }

        $this->closeDetailModal();
    }

    public function terminateLease($mappingId, \App\Services\LeaseService $leaseService)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        $leaseService->terminateLease($mapping);
        $this->dispatch('notify', type: 'success', message: 'Lease terminated successfully. Stall is now available.');
        $this->closeDetailModal();
    }

    public function render()
    {
        $query = StallMapping::with(['user', 'stall.location', 'initialInvoice']);

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })->orWhereHas('stall', function ($q) {
                $q->where('stall_number', 'like', "%{$this->search}%");
            });
        }

        if ($this->locationFilter) {
            $query->whereHas('stall', function ($q) {
                $q->where('location_id', $this->locationFilter);
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

        return view('livewire.admin.lease-mapping-manager', [
            'mappings' => $query->latest()->paginate(10),
            'locations' => StallLocation::orderBy('sort_order')->get(),
            'users' => User::where('role', 'user')->orderBy('name')->get(),
            'availableStalls' => Stall::where('status', 'available')->orderBy('stall_number')->get(),
        ]);
    }
}
