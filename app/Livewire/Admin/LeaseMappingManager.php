<?php

namespace App\Livewire\Admin;

use App\Models\Stall;
use App\Models\StallLocation;
use App\Models\StallMapping;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\InvoiceService;
use App\Services\LeaseService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class LeaseMappingManager extends Component
{
    use WithPagination, WithFileUploads;

    #[Url(except: '')]
    public $search = '';
    public $locationFilter = '';
    public $statusFilter = '';

    // Exact stall number (calendar links); ?stall= is taken by the New Lease pre-fill (a stall id).
    #[Url(as: 'stall_number', except: '')]
    public string $stallNumber = '';
    public $showModal = false;

    // Lease Detail & Actions Modal
    public $selectedLease = null;
    public $showDetailModal = false;

    // Renewal review (detail modal)
    public string $renewalEndDate = '';
    public int $renewalDurationMonths = 6;
    public string $renewalRejectReason = '';

    // Termination modal
    public ?int $terminateId = null;
    public float $terminateBalance = 0;
    public string $terminationReason = '';
    public bool $acknowledgeBalance = false;

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
    public string $duration = '1';

    public function updatedDuration(): void
    {
        $this->recalculateEndDate();
    }

    public function updatedStartDate(): void
    {
        $this->recalculateEndDate();
    }

    private function recalculateEndDate(): void
    {
        if (!ctype_digit($this->duration) || (int) $this->duration < 1 || (int) $this->duration > 12) {
            return;
        }
        $validator = validator(['start_date' => $this->start_date], ['start_date' => 'required|date_format:Y-m-d']);
        if ($validator->fails()) {
            return;
        }
        $this->end_date = \Illuminate\Support\Carbon::parse($this->start_date, 'Asia/Manila')
            ->addMonthsNoOverflow((int) $this->duration)->toDateString();
    }
    public $contract_file = null;

    protected $rules = [
        'duration' => 'required|integer|between:1,12',
        'user_id' => 'required|exists:users,id',
        'stall_id' => 'required|exists:stalls,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'contract_file' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
    ];

    public function openDetailModal($id)
    {
        $this->resetValidation();
        $this->selectedLease = StallMapping::with(['user', 'stall.location', 'initialInvoice'])->findOrFail($id);
        $this->renewalEndDate = $this->selectedLease->requested_end_date?->toDateString() ?? '';
        $this->renewalDurationMonths = $this->selectedLease->renewal_months ?? 6;
        $this->renewalRejectReason = '';
        $this->showDetailModal = true;
    }

    public function approveRenewal(int $id, LeaseService $leaseService): void
    {
        $lease = StallMapping::findOrFail($id);
        $this->validate(['renewalDurationMonths' => ['required', 'integer', 'between:1,12']]);
        $this->renewalEndDate = $lease->end_date->copy()->addMonthsNoOverflow($this->renewalDurationMonths)->toDateString();

        try {
            $leaseService->approveRenewal($lease, auth()->user(), Carbon::parse($this->renewalEndDate));
        } catch (\InvalidArgumentException $e) {
            $this->addError('renewalEndDate', $e->getMessage());
            return;
        }

        $this->notifyRenewalOutcome($leaseService, 'Renewal reviewed. The invoice must be paid before the lease is extended.');
        $this->closeDetailModal();
    }

    public function rejectRenewal(int $id, LeaseService $leaseService): void
    {
        $lease = StallMapping::findOrFail($id);
        $this->validate(['renewalRejectReason' => ['required', 'string', 'max:500']], [], ['renewalRejectReason' => 'reason']);

        try {
            $leaseService->rejectRenewal($lease, auth()->user(), $this->renewalRejectReason);
        } catch (\InvalidArgumentException $e) {
            $this->addError('renewalRejectReason', $e->getMessage());
            return;
        }

        $this->notifyRenewalOutcome($leaseService, 'Renewal rejected.');
        $this->closeDetailModal();
    }

    private function notifyRenewalOutcome(LeaseService $leaseService, string $done): void
    {
        if ($leaseService->lastNotificationSent) {
            $this->dispatch('notify', type: 'success', message: "{$done} The tenant was notified.");
        } else {
            $this->dispatch('notify', type: 'warning', message: "{$done} The tenant email or SMS could not be sent.");
        }
    }

    public function recordRenewalCashPayment(int $id, InvoiceService $invoiceService): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $lease = StallMapping::with('renewalInvoice')->findOrFail($id);
        if ($lease->renewal_status !== 'awaiting_payment' || !$lease->renewalInvoice) {
            $this->addError('renewalPayment', 'There is no renewal invoice awaiting payment.');
            return;
        }
        $invoiceService->markAsPaid($lease->renewalInvoice, 'office_cash', 'RENEW-CASH-' . \Illuminate\Support\Str::uuid());
        $this->dispatch('notify', type: 'success', message: 'Full renewal payment recorded. The lease is extended.');
        $this->closeDetailModal();
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedLease = null;
    }

    public function mount(): void
    {
        // The dashboard's "Renewal requests" card links here with ?status=renewal_requested.
        $status = request()->query('status');
        if (in_array($status, ['active', 'pending', 'inactive', 'renewal_requested'], true)) {
            $this->statusFilter = $status;
        }

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
        $this->duration = '1';
        $this->start_date = now('Asia/Manila')->toDateString();
        $this->recalculateEndDate();
        $this->showDetailModal = false;
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveMapping(\App\Services\LeaseService $leaseService)
    {
        $this->recalculateEndDate();
        $this->validate();

        try {
            $leaseService->createLease(
                (int) $this->user_id,
                (int) $this->stall_id,
                $this->start_date,
                $this->end_date,
                $this->contract_file,
                (int) $this->duration
            );

            $this->dispatch('notify', type: 'success', message: 'Stall assigned. Full-term rent invoice generated; the stall is reserved until payment.');
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
            $this->dispatch('notify', type: 'success', message: 'Office cash payment recorded. Lease is Active and Paid; stall is Occupied.');
        }

        $this->closeDetailModal();
    }

    public function openTerminate(int $id, LeaseService $leaseService): void
    {
        $this->resetValidation();
        $lease = StallMapping::findOrFail($id);
        $this->terminateId = $lease->id;
        $this->terminateBalance = $leaseService->unpaidBalance($lease);
        $this->terminationReason = '';
        $this->acknowledgeBalance = false;
    }

    public function closeTerminate(): void
    {
        $this->terminateId = null;
        $this->resetValidation(['terminationReason', 'acknowledgeBalance']);
    }

    public function terminateLease(LeaseService $leaseService)
    {
        $lease = StallMapping::findOrFail($this->terminateId);
        if (!$leaseService->canTerminate($lease)) {
            // e.g. terminated meanwhile from another tab.
            $this->terminateId = null;
            $this->dispatch('notify', type: 'error', message: 'This lease has ended or its renewal is awaiting payment.');
            return;
        }

        // Recomputed here so a payment made while the modal was open is respected.
        $this->terminateBalance = $leaseService->unpaidBalance($lease);

        $this->validate([
            'terminationReason' => 'required|string|min:10|max:500',
            'acknowledgeBalance' => $this->terminateBalance > 0 ? 'accepted' : 'nullable',
        ], [
            'acknowledgeBalance.accepted' => 'Confirm that the unpaid balance stays owed by the tenant.',
        ]);

        $leaseService->terminateLease($lease, trim($this->terminationReason));
        $this->dispatch('notify', type: 'success', message: 'Lease terminated. Stall is now available.');
        $this->terminateId = null;
        $this->closeDetailModal();
    }

    public function render()
    {
        $query = StallMapping::with(['user', 'stall.location', 'initialInvoice']);

        if ($this->search) {
            // Grouped so the floor and status filters below still apply to every match.
            $query->where(function ($w) {
                $w->whereHas('user', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                })->orWhereHas('stall', function ($q) {
                    $q->where('stall_number', 'like', "%{$this->search}%");
                });
            });
        }

        if ($this->stallNumber !== '') {
            $query->whereHas('stall', fn ($q) => $q->where('stall_number', $this->stallNumber));
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
            } elseif ($this->statusFilter === 'renewal_requested') {
                $query->where('is_active', true)->whereIn('renewal_status', ['requested', 'awaiting_payment']);
            }
        }

        $assignmentStall = $this->stall_id ? Stall::find($this->stall_id) : null;
        $validDuration = ctype_digit($this->duration) && (int) $this->duration >= 1 && (int) $this->duration <= 12;
        return view('livewire.admin.lease-mapping-manager', [
            'assignmentMonthlyRate' => $assignmentStall?->price,
            'assignmentRentTotal' => $assignmentStall && $validDuration
                ? \App\Services\LeaseService::calculateContractRent($assignmentStall->price, (int) $this->duration)
                : null,
            'mappings' => $query->latest()->paginate(10),
            'locations' => StallLocation::orderBy('sort_order')->get(),
            'users' => User::where('role', 'user')->orderBy('name')->get(),
            'availableStalls' => Stall::where('status', 'available')->orderBy('stall_number')->get(),
        ]);
    }
}
