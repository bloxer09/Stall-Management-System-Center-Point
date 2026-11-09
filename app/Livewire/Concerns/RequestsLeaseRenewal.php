<?php

namespace App\Livewire\Concerns;

use App\Models\StallMapping;
use App\Services\LeaseService;
use InvalidArgumentException;

/**
 * Tenant-side "request lease renewal" modal state and actions, shared by the tenant
 * dashboard and My Leases. The rules themselves live in LeaseService.
 */
trait RequestsLeaseRenewal
{
    public ?int $renewalLeaseId = null;
    public string $renewalNote = '';
    public int $renewalMonths = 6;

    public function openRenewal(int $id, LeaseService $leases): void
    {
        $this->resetValidation();
        $lease = StallMapping::where('user_id', auth()->id())->find($id);

        $this->renewalLeaseId = $lease && $leases->isRenewalEligible($lease) ? $lease->id : null;
        $this->renewalNote = '';
        $this->renewalMonths = 6;
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

        $this->validate(['renewalMonths' => ['required', 'integer', 'between:1,12']]);
        $requestedEnd = $lease->end_date->copy()->addMonthsNoOverflow($this->renewalMonths);

        try {
            $leases->requestRenewal($lease, auth()->user(), $requestedEnd, mb_substr($this->renewalNote, 0, 500));
        } catch (InvalidArgumentException $e) {
            $this->addError('renewal', $e->getMessage());
            return;
        }

        $this->renewalLeaseId = null;
        $this->dispatch('notify', type: 'success', message: 'Renewal request sent. The office will review it.');
    }

    /**
     * The tenant's own lease whose renewal modal is open, if any.
     */
    public function renewalLease(): ?StallMapping
    {
        return $this->renewalLeaseId
            ? StallMapping::with('stall')->where('user_id', auth()->id())->find($this->renewalLeaseId)
            : null;
    }
}
