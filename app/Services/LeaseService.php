<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallMapping;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeaseService
{
    private const RENEWAL_WINDOW_DAYS = 60;

    /**
     * Whether the tenant email/SMS of the last approveRenewal()/rejectRenewal() call went out.
     * The state change is never rolled back for a failed send; callers warn the admin instead.
     */
    public bool $lastNotificationSent = true;

    public function __construct(
        protected FileUploadService $fileUploader,
        protected MailService $mailer,
        protected SmsService $sms,
    ) {}

    /**
     * A tenant may ask to renew an active lease once it is within 60 days of its end
     * (the first expiry warning), unless a request is already waiting for review.
     */
    public function isRenewalEligible(StallMapping $lease): bool
    {
        return $lease->is_active
            && $lease->end_date !== null
            && $lease->end_date->lte(now()->addDays(self::RENEWAL_WINDOW_DAYS)->endOfDay())
            && $lease->renewal_status !== 'requested';
    }

    /**
     * @throws InvalidArgumentException with a tenant-readable message when the request is not allowed.
     */
    public function requestRenewal(StallMapping $lease, User $tenant, Carbon $requestedEnd, ?string $note = null): StallMapping
    {
        if ($lease->user_id !== $tenant->id) {
            throw new InvalidArgumentException('You can only renew your own lease.');
        }
        if (!$lease->is_active) {
            throw new InvalidArgumentException('Only active leases can be renewed.');
        }
        if ($lease->renewal_status === 'requested') {
            throw new InvalidArgumentException('A renewal request for this lease is already waiting for review.');
        }
        if (!$this->isRenewalEligible($lease)) {
            throw new InvalidArgumentException('Renewal opens 60 days before your lease ends.');
        }
        if ($requestedEnd->lte($lease->end_date)) {
            throw new InvalidArgumentException('The new end date must be after your current end date.');
        }

        DB::transaction(function () use ($lease, $requestedEnd, $note) {
            $lease->update([
                'renewal_status' => 'requested',
                'requested_end_date' => $requestedEnd->toDateString(),
                'renewal_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'renewal_rejection_reason' => null,
            ]);

            AuditLog::record(
                "Renewal requested for Lease #{$lease->id} until {$requestedEnd->toDateString()}",
                'StallMapping',
                $lease->id,
                $note
            );
        });

        $stallNumber = $lease->stall?->stall_number;
        foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
            Notification::send(
                $adminId,
                'renewal',
                'Lease renewal requested',
                "{$tenant->name} asked to renew Stall {$stallNumber} until {$requestedEnd->format('M d, Y')}.",
                route('admin.mappings')
            );
        }

        return $lease->fresh();
    }

    /**
     * @throws InvalidArgumentException when there is no pending request or the date is invalid.
     */
    public function approveRenewal(StallMapping $lease, User $admin, ?Carbon $overrideEnd = null): StallMapping
    {
        $lease = DB::transaction(function () use ($lease, $admin, $overrideEnd) {
            $locked = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if ($locked->renewal_status !== 'requested') {
                throw new InvalidArgumentException('There is no pending renewal request for this lease.');
            }

            $newEnd = $overrideEnd ?? $locked->requested_end_date;
            if (!$newEnd || $newEnd->lte($locked->end_date)) {
                throw new InvalidArgumentException('The new end date must be after the current end date.');
            }

            $locked->update([
                'end_date' => $newEnd->toDateString(),
                'renewal_status' => 'approved',
                'renewal_rejection_reason' => null,
            ]);

            AuditLog::record(
                "Approved renewal for Lease #{$locked->id} until {$newEnd->toDateString()}",
                'StallMapping',
                $locked->id,
                null,
                $admin->id
            );

            return $locked->fresh(['user', 'stall']);
        });

        $tenant = $lease->user;
        $stallNumber = $lease->stall?->stall_number;
        $until = $lease->end_date->format('M d, Y');
        $message = "Your lease renewal for Stall {$stallNumber} was approved. Your lease now ends on {$until}.";

        Notification::send($tenant->id, 'renewal', 'Lease renewal approved', $message, route('user.leases'));
        $sent = $this->mailer->sendEmail($tenant->email, $tenant->name, 'Your lease renewal was approved', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);
        if ($tenant->phone) {
            $sent = $this->sms->sendSms($tenant->phone, "STALLM: {$message}") && $sent;
        }
        $this->lastNotificationSent = $sent;

        return $lease;
    }

    /**
     * @throws InvalidArgumentException when the reason is empty or there is no pending request.
     */
    public function rejectRenewal(StallMapping $lease, User $admin, string $reason): StallMapping
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Please give a reason for rejecting the renewal.');
        }

        $lease = DB::transaction(function () use ($lease, $admin, $reason) {
            $locked = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if ($locked->renewal_status !== 'requested') {
                throw new InvalidArgumentException('There is no pending renewal request for this lease.');
            }

            $locked->update([
                'renewal_status' => 'rejected',
                'renewal_rejection_reason' => $reason,
            ]);

            AuditLog::record("Rejected renewal for Lease #{$locked->id}", 'StallMapping', $locked->id, $reason, $admin->id);

            return $locked->fresh(['user', 'stall']);
        });

        $tenant = $lease->user;
        $stallNumber = $lease->stall?->stall_number;
        $message = "Your lease renewal request for Stall {$stallNumber} was not approved. Reason: {$reason}";

        Notification::send($tenant->id, 'renewal', 'Lease renewal not approved', $message, route('user.leases'));
        $this->lastNotificationSent = $this->mailer->sendEmail($tenant->email, $tenant->name, 'Update on your lease renewal', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);

        return $lease;
    }

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
     * Keep a lease in good standing when its monthly rental invoice is paid.
     * Paying never moves end_date; leases are extended only through an approved renewal request.
     */
    public function markGoodStandingOnPayment(Invoice $invoice): ?StallMapping
    {
        $lease = StallMapping::where('user_id', $invoice->user_id)
            ->where('stall_id', $invoice->stall_id)
            ->where('is_active', true)
            ->first();

        if ($lease) {
            $lease->update(['payment_status' => 'good_standing']);

            AuditLog::record(
                "Lease #{$lease->id} marked Good Standing via Invoice #{$invoice->invoice_number}",
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
