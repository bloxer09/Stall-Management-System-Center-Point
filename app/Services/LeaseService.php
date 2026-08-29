<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Stall;
use App\Models\StallMapping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaseService
{
    public function __construct(
        protected FileUploadService $fileUploader
    ) {}

    /**
     * Create a new stall lease agreement in Pending Initial Payment status.
     * The stall is reserved until payment is completed via PayMongo or Cash.
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
        $hasOccupyingLease = StallMapping::where('stall_id', $stallId)
            ->where(function ($q) {
                $q->where('is_active', true)
                  ->orWhere('payment_status', 'pending_initial');
            })
            ->exists();

        if ($hasOccupyingLease) {
            throw new InvalidArgumentException("Stall #{$stallId} already has an active or reserved lease assignment.");
        }

        $filePath = null;
        if ($contractFile) {
            $filePath = $this->fileUploader->uploadContractDocument($contractFile);
        }

        $stall = Stall::findOrFail($stallId);

        $lease = DB::transaction(function () use ($userId, $stall, $startDate, $endDate, $filePath) {
            // 1. Create Lease Mapping in Pending Initial Payment state
            $created = StallMapping::create([
                'user_id' => $userId,
                'stall_id' => $stall->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => false,
                'payment_status' => 'pending_initial',
                'contract_document' => $filePath,
                'renewal_status' => 'none',
            ]);

            // 2. Mark stall as reserved
            $stall->update(['status' => 'reserved']);

            // 3. Automatically create initial rental invoice
            $periodDate = Carbon::parse($startDate);
            $billingPeriod = $periodDate->format('Y-m');
            $invoiceNumber = 'INV-' . $periodDate->format('Ym') . '-' . str_pad((string) $stall->id, 4, '0', STR_PAD_LEFT);

            // If an invoice already exists for this stall & billing period, make unique invoice number
            if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . $periodDate->format('Ym') . '-' . str_pad((string) $stall->id, 4, '0', STR_PAD_LEFT) . '-' . time();
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $userId,
                'stall_id' => $stall->id,
                'total_amount' => $stall->price,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(7)->toDateString(),
                'billing_period' => $billingPeriod,
                'notes' => 'Initial Stall Lease Deposit & 1st Month Rent - Stall #' . $stall->stall_number,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Initial Stall Lease Deposit & 1st Month Rent - Stall #' . $stall->stall_number,
                'amount' => $stall->price,
                'item_type' => 'rent',
            ]);

            // 4. Attach initial invoice to lease
            $created->update(['initial_invoice_id' => $invoice->id]);

            return $created;
        });

        AuditLog::record(
            "Assigned Stall #{$lease->stall_id} to User #{$userId} (Pending Initial Payment)",
            'StallMapping',
            $lease->id
        );

        return $lease;
    }

    /**
     * Activate a pending lease upon confirmation of initial payment.
     */
    public function activateLeaseOnPayment(StallMapping $lease): StallMapping
    {
        DB::transaction(function () use ($lease) {
            $lease->update([
                'is_active' => true,
                'payment_status' => 'good_standing',
            ]);

            Stall::where('id', $lease->stall_id)->update(['status' => 'occupied']);
        });

        AuditLog::record(
            "Lease mapping #{$lease->id} activated upon payment. Stall #{$lease->stall_id} is now occupied.",
            'StallMapping',
            $lease->id
        );

        return $lease->fresh();
    }

    /**
     * Renew/maintain lease in good standing when ongoing monthly rental invoice is paid.
     */
    public function renewLeaseOnPayment(Invoice $invoice): ?StallMapping
    {
        $lease = StallMapping::where('user_id', $invoice->user_id)
            ->where('stall_id', $invoice->stall_id)
            ->where('is_active', true)
            ->first();

        if ($lease) {
            // If current end date has passed or is within 30 days, advance by 1 month
            $newEndDate = $lease->end_date;
            if ($newEndDate->isPast() || $newEndDate->diffInDays(now()) <= 30) {
                $newEndDate = $newEndDate->copy()->addMonth();
            }

            $lease->update([
                'payment_status' => 'good_standing',
                'end_date' => $newEndDate,
            ]);

            AuditLog::record(
                "Lease #{$lease->id} renewed / maintained in Good Standing via Invoice #{$invoice->invoice_number}",
                'StallMapping',
                $lease->id
            );
        }

        return $lease;
    }

    /**
     * Terminate an active or reserved lease and make the stall available again.
     */
    public function terminateLease(StallMapping $lease): StallMapping
    {
        DB::transaction(function () use ($lease) {
            $lease->update([
                'is_active' => false,
                'payment_status' => 'settled',
            ]);
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
