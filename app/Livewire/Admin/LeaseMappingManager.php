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

    public function openModal()
    {
        $this->resetValidation();
        $this->reset(['mappingId', 'user_id', 'stall_id', 'start_date', 'end_date', 'contract_file']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function saveMapping(FileUploadService $uploader)
    {
        $this->validate();

        $alreadyActive = StallMapping::where('stall_id', $this->stall_id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyActive) {
            $this->addError('stall_id', 'This stall already has an active lease. Please terminate the existing lease before creating a new one.');
            return;
        }

        $filePath = null;
        if ($this->contract_file) {
            $filePath = $uploader->uploadContractDocument($this->contract_file);
        }

        $mapping = \Illuminate\Support\Facades\DB::transaction(function () use ($filePath) {
            $map = StallMapping::create([
                'user_id' => $this->user_id,
                'stall_id' => $this->stall_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'is_active' => true,
                'contract_document' => $filePath,
            ]);

            Stall::where('id', $this->stall_id)->update(['status' => 'occupied']);
            return $map;
        });

        \App\Models\AuditLog::record("Created lease mapping #{$mapping->id} for Stall #{$mapping->stall_id}", 'StallMapping', $mapping->id);

        session()->flash('message', 'Lease mapping created successfully!');
        $this->closeModal();
    }

    public function approveRenewal($mappingId)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        if ($mapping->renewal_status === 'requested' && $mapping->requested_end_date) {
            $mapping->update([
                'end_date' => $mapping->requested_end_date,
                'renewal_status' => 'approved',
                'requested_end_date' => null,
            ]);

            \App\Models\AuditLog::record("Approved lease renewal for mapping #{$mappingId}", 'StallMapping', $mappingId);
            session()->flash('message', 'Renewal request approved!');
        }
    }

    public function rejectRenewal($mappingId)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        $mapping->update([
            'renewal_status' => 'rejected',
            'requested_end_date' => null,
        ]);

        \App\Models\AuditLog::record("Rejected lease renewal for mapping #{$mappingId}", 'StallMapping', $mappingId);
        session()->flash('message', 'Renewal request rejected.');
    }

    public function terminateLease($mappingId)
    {
        $mapping = StallMapping::findOrFail($mappingId);
        \Illuminate\Support\Facades\DB::transaction(function () use ($mapping) {
            $mapping->update(['is_active' => false]);
            Stall::where('id', $mapping->stall_id)->update(['status' => 'available']);
        });

        \App\Models\AuditLog::record("Terminated lease mapping #{$mappingId}", 'StallMapping', $mappingId);
        session()->flash('message', 'Lease terminated successfully.');
    }

    public function render()
    {
        $query = StallMapping::with(['user', 'stall']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($u) {
                    $u->where('name', 'like', "%{$this->search}%");
                })->orWhereHas('stall', function ($s) {
                    $s->where('stall_number', 'like', "%{$this->search}%");
                });
            });
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'requested') {
            $query->where('renewal_status', 'requested');
        } elseif ($this->statusFilter === 'terminated') {
            $query->where('is_active', false);
        }

        return view('livewire.admin.lease-mapping-manager', [
            'mappings' => $query->latest()->paginate(10),
            'availableUsers' => User::where('role', 'user')->get(),
            'availableStalls' => Stall::where('status', 'available')->get(),
        ]);
    }
}
