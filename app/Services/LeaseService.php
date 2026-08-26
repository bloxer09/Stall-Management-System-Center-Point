<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Stall;
use App\Models\StallMapping;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaseService
{
    public function __construct(
        protected FileUploadService $fileUploader
    ) {}

    /**
     * Create and activate a new stall lease agreement.
     *
     * @throws InvalidArgumentException
     */
    public function createLease(
        int $userId,
        int $stallId,
        string $startDate,
        string $endDate,
        mixed $contractFile = null
    ): StallMapping {
        $hasActiveLease = StallMapping::where('stall_id', $stallId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveLease) {
            throw new InvalidArgumentException("Stall #{$stallId} already has an active lease agreement.");
        }

        $filePath = null;
        if ($contractFile) {
            $filePath = $this->fileUploader->uploadContractDocument($contractFile);
        }

        $lease = DB::transaction(function () use ($userId, $stallId, $startDate, $endDate, $filePath) {
            $created = StallMapping::create([
                'user_id' => $userId,
                'stall_id' => $stallId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => true,
                'contract_document' => $filePath,
                'renewal_status' => 'none',
            ]);

            Stall::where('id', $stallId)->update(['status' => 'occupied']);

            return $created;
        });

        AuditLog::record(
            "Created lease mapping #{$lease->id} for Stall #{$lease->stall_id}",
            'StallMapping',
            $lease->id
        );

        return $lease;
    }

    /**
     * Submit a lease extension/renewal request from a tenant.
     */
    public function requestRenewal(StallMapping $lease, string $requestedEndDate): StallMapping
    {
        $lease->update([
            'renewal_status' => 'requested',
            'requested_end_date' => $requestedEndDate,
        ]);

        return $lease;
    }

    /**
     * Approve a pending lease renewal request.
     */
    public function approveRenewal(StallMapping $lease): StallMapping
    {
        if ($lease->renewal_status === 'requested' && $lease->requested_end_date) {
            $lease->update([
                'end_date' => $lease->requested_end_date,
                'renewal_status' => 'approved',
                'requested_end_date' => null,
            ]);

            AuditLog::record(
                "Approved lease renewal for mapping #{$lease->id}",
                'StallMapping',
                $lease->id
            );
        }

        return $lease;
    }

    /**
     * Reject a pending lease renewal request.
     */
    public function rejectRenewal(StallMapping $lease): StallMapping
    {
        $lease->update([
            'renewal_status' => 'rejected',
            'requested_end_date' => null,
        ]);

        AuditLog::record(
            "Rejected lease renewal for mapping #{$lease->id}",
            'StallMapping',
            $lease->id
        );

        return $lease;
    }

    /**
     * Terminate an active lease and make the stall available again.
     */
    public function terminateLease(StallMapping $lease): StallMapping
    {
        DB::transaction(function () use ($lease) {
            $lease->update(['is_active' => false]);
            Stall::where('id', $lease->stall_id)->update(['status' => 'available']);
        });

        AuditLog::record(
            "Terminated lease mapping #{$lease->id}",
            'StallMapping',
            $lease->id
        );

        return $lease;
    }
}
