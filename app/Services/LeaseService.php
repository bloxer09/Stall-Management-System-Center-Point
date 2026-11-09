<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Stall;
use App\Models\StallMapping;
use App\Models\User;
use DomainException;
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
            && !in_array($lease->renewal_status, ['requested', 'awaiting_payment'], true);
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
        if (in_array($lease->renewal_status, ['requested', 'awaiting_payment'], true)) {
            throw new InvalidArgumentException('A renewal request for this lease is already waiting for review.');
        }
        if (!$this->isRenewalEligible($lease)) {
            throw new InvalidArgumentException('Renewal opens 60 days before your lease ends.');
        }
        if ($requestedEnd->lte($lease->end_date)) {
            throw new InvalidArgumentException('The new end date must be after your current end date.');
        }

        $months = $this->renewalMonths($lease->end_date, $requestedEnd);

        DB::transaction(function () use ($lease, $requestedEnd, $note, $months) {
            $lease = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if (!$this->isRenewalEligible($lease)) {
                throw new InvalidArgumentException('This lease already has a pending request or is no longer eligible.');
            }
            $this->renewalMonths($lease->end_date, $requestedEnd);
            $lease->update([
                'renewal_status' => 'requested',
                'requested_end_date' => $requestedEnd->toDateString(),
                'renewal_note' => $note !== null && trim($note) !== '' ? trim($note) : null,
                'renewal_rejection_reason' => null,
                'renewal_months' => $months,
                'renewal_invoice_id' => null,
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
        if (!$admin->isAdmin()) {
            throw new InvalidArgumentException('Only an admin can review renewals.');
        }
        $reviewed = false;
        $lease = DB::transaction(function () use ($lease, $admin, $overrideEnd, &$reviewed) {
            $locked = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if ($locked->renewal_status === 'awaiting_payment') {
                return $locked->fresh(['user', 'stall', 'renewalInvoice']);
            }
            if ($locked->renewal_status !== 'requested') {
                throw new InvalidArgumentException('There is no pending renewal request for this lease.');
            }

            $newEnd = $overrideEnd ?? $locked->requested_end_date;
            if (!$newEnd || $newEnd->lte($locked->end_date)) {
                throw new InvalidArgumentException('The new end date must be after the current end date.');
            }

            if (!$locked->is_active) {
                throw new InvalidArgumentException('Only active leases can be renewed.');
            }
            $months = $this->renewalMonths($locked->end_date, $newEnd);
            $rate = (string) ($locked->agreed_monthly_rate ?? $locked->stall->price);
            $amount = self::calculateContractRent($rate, $months);
            if ((float) $amount <= 0) {
                throw new InvalidArgumentException('Renewal rent must be greater than zero.');
            }
            $invoice = Invoice::create([
                'invoice_number' => 'INV-RENEW-' . $locked->id . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
                'user_id' => $locked->user_id, 'stall_id' => $locked->stall_id,
                'stall_mapping_id' => $locked->id, 'total_amount' => $amount,
                'status' => 'pending', 'due_date' => today()->addDays(7),
                'notes' => 'Lease renewal rent', 'is_renewal' => true,
                'rent_period_start' => $locked->end_date, 'rent_period_end' => $newEnd,
                'renewal_months' => $months, 'renewal_monthly_rate' => $rate,
            ]);
            InvoiceItem::create([
                'invoice_id' => $invoice->id, 'item_type' => 'rent', 'amount' => $amount,
                'description' => "Renewal rent: {$months} month(s), " . $locked->end_date->toDateString() . ' to ' . $newEnd->toDateString(),
            ]);
            $reviewed = true;

            $locked->update([
                'requested_end_date' => $newEnd->toDateString(),
                'renewal_status' => 'awaiting_payment',
                'renewal_months' => $months,
                'renewal_invoice_id' => $invoice->id,
                'renewal_rejection_reason' => null,
            ]);

            AuditLog::record(
                "Reviewed renewal for Lease #{$locked->id}; Invoice #{$invoice->id} awaits payment",
                'StallMapping',
                $locked->id,
                null,
                $admin->id
            );

            return $locked->fresh(['user', 'stall']);
        });

        if ($reviewed) {
            $message = 'The office reviewed your renewal. Pay the full renewal invoice to extend your lease.';
            Notification::send($lease->user_id, 'renewal', 'Renewal ready for payment', $message, route('user.payments'));
            $this->lastNotificationSent = $this->mailer->sendEmail($lease->user->email, $lease->user->name,
                'Renewal ready for payment', '<p>' . e($message) . '</p>', $message);
        }
        return $lease;
    }

    public function finalizePaidRenewal(Invoice $invoice): void
    {
        $lease = DB::transaction(function () use ($invoice) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $lease = StallMapping::where('renewal_invoice_id', $invoice->id)->lockForUpdate()->first();
            if (!$lease || !$invoice->is_renewal || $invoice->status !== 'paid'
                || $lease->renewal_status !== 'awaiting_payment') {
                return null;
            }
            if (!$invoice->rent_period_start?->isSameDay($lease->end_date)
                || !$invoice->rent_period_end?->isSameDay($lease->requested_end_date)
                || $invoice->user_id !== $lease->user_id) {
                throw new DomainException('The paid renewal invoice does not match the reviewed lease terms.');
            }
            $lease->update([
                'end_date' => $invoice->rent_period_end,
                'renewal_status' => 'approved',
            ]);
            AuditLog::record("Paid renewal approved for Lease #{$lease->id}", 'StallMapping', $lease->id);
            return $lease->fresh(['user', 'stall']);
        });
        if (!$lease) {
            return;
        }

        $tenant = $lease->user;
        $stallNumber = $lease->stall?->stall_number;
        $until = $lease->end_date->format('M d, Y');
        $message = "Your lease renewal for Stall {$stallNumber} was approved. Your lease now ends on {$until}.";

        Notification::send($tenant->id, 'renewal', 'Lease renewal approved', $message, route('user.leases'));
        $sent = $this->mailer->sendEmail($tenant->email, $tenant->name, 'Your lease renewal was approved', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);
        // SMS switched off in System Settings is a choice, not a delivery failure.
        if ($tenant->phone && $this->sms->enabled()) {
            $sent = $this->sms->sendSms($tenant->phone, "STALLM: {$message}") && $sent;
        }
        $this->lastNotificationSent = $sent;

    }

    private function renewalMonths(Carbon $start, Carbon $end): int
    {
        for ($months = 1; $months <= 12; $months++) {
            if ($start->copy()->addMonthsNoOverflow($months)->isSameDay($end)) {
                return $months;
            }
        }
        throw new InvalidArgumentException('Choose a renewal of 1 to 12 whole months.');
    }

    /**
     * @throws InvalidArgumentException when the reason is empty or there is no pending request.
     */
    public function rejectRenewal(StallMapping $lease, User $admin, string $reason): StallMapping
    {
        if (!$admin->isAdmin()) {
            throw new InvalidArgumentException('Only an admin can review renewals.');
        }
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
        mixed $contractFile = null,
        ?int $durationMonths = null
    ): StallMapping {
        if ($durationMonths !== null) {
            if ($durationMonths < 1 || $durationMonths > 12) {
                throw new InvalidArgumentException('Contract duration must be between 1 and 12 months.');
            }
            validator(['start_date' => $startDate], ['start_date' => 'required|date_format:Y-m-d'])->validate();
            $endDate = Carbon::parse($startDate, 'Asia/Manila')->addMonthsNoOverflow($durationMonths)->toDateString();
        }
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

        $lease = DB::transaction(function () use ($userId, $stall, $startDate, $endDate, $filePath, $durationMonths) {
            $stall = Stall::whereKey($stall->id)->lockForUpdate()->firstOrFail();
            $occupied = StallMapping::where('stall_id', $stall->id)
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('payment_status', 'pending_initial'))
                ->exists();
            if ($occupied) {
                throw new InvalidArgumentException("Stall #{$stall->id} already has an active or reserved lease assignment.");
            }
            $rent = self::calculateContractRent($stall->price, $durationMonths ?? 1);
            $description = $durationMonths !== null
                ? "Stall #{$stall->stall_number} rent: {$durationMonths} month(s), {$startDate} to {$endDate}"
                : 'Initial Stall Lease Deposit & 1st Month Rent - Stall #' . $stall->stall_number;
            // 1. Create Lease Mapping in Pending Initial Payment state
            $created = StallMapping::create([
                'user_id' => $userId,
                'stall_id' => $stall->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_months' => $durationMonths,
                'agreed_monthly_rate' => $durationMonths !== null ? $stall->price : null,
                'contract_rent' => $durationMonths !== null ? $rent : null,
                'rent_billing_mode' => $durationMonths !== null ? 'upfront' : 'monthly',
                'upfront_covered_until' => $durationMonths !== null ? $endDate : null,
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

            if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber .= '-L' . $created->id;
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $userId,
                'stall_id' => $stall->id,
                'stall_mapping_id' => $created->id,
                'total_amount' => $rent,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(7)->toDateString(),
                'billing_period' => $billingPeriod,
                'notes' => $description,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $description,
                'amount' => $rent,
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
        $activated = DB::transaction(function () use ($lease) {
            $invoice = Invoice::whereKey($lease->initial_invoice_id)->lockForUpdate()->first();
            $lease = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if (!$invoice?->isPaid() || !$lease->isPendingInitial() || $lease->terminated_at) {
                return false;
            }
            $lease->update([
                'is_active' => true,
                'payment_status' => 'good_standing',
            ]);

            Stall::where('id', $lease->stall_id)->update(['status' => 'occupied']);
            return true;
        });

        if ($activated) {
            AuditLog::record(
                "Lease mapping #{$lease->id} activated upon payment. Stall #{$lease->stall_id} is now occupied.",
                'StallMapping',
                $lease->id
            );
        }

        return $lease->fresh();
    }

    public function cancelExpiredReservation(int $invoiceId): bool
    {
        $cancelled = DB::transaction(function () use ($invoiceId) {
            // Payment confirmation locks the invoice before the lease as well.
            $invoice = Invoice::whereKey($invoiceId)->lockForUpdate()->first();
            if (!$invoice || !$invoice->isOverdue() || $invoice->is_renewal) {
                return null;
            }
            $lease = StallMapping::where('initial_invoice_id', $invoiceId)->lockForUpdate()->first();
            if (!$lease || !$lease->isPendingInitial() || $lease->is_active || $lease->terminated_at
                || $lease->user_id !== $invoice->user_id || $lease->stall_id !== $invoice->stall_id) {
                return null;
            }
            $stall = Stall::whereKey($lease->stall_id)->lockForUpdate()->firstOrFail();
            $reason = 'Initial payment was not received by the seven-day payment deadline (' . $invoice->due_date->format('M d, Y') . ').';
            $balance = $this->unpaidBalance($lease);
            $invoice->update([
                'status' => 'cancelled',
                'notes' => trim(($invoice->notes ? $invoice->notes . ' | ' : '') . 'Cancelled: unpaid reservation deadline passed'),
            ]);
            $lease->update([
                'is_active' => false, 'payment_status' => $balance > 0 ? 'past_due' : 'settled',
                'terminated_at' => now(), 'termination_reason' => $reason,
            ]);
            $otherAssignment = StallMapping::where('stall_id', $stall->id)->whereKeyNot($lease->id)
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('payment_status', 'pending_initial'))->exists();
            if ($stall->status === 'reserved' && !$otherAssignment) {
                $stall->update(['status' => 'available']);
            }
            $message = "Your reservation for Stall {$stall->stall_number} was cancelled because the initial invoice was unpaid after its due date. The initial invoice has been cancelled. Contact the office if you already sent a payment.";
            Notification::send($lease->user_id, 'lease', 'Reservation cancelled', $message, route('user.leases'));
            foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                Notification::send($adminId, 'lease', 'Unpaid reservation cancelled', "Reservation #{$lease->id} for Stall {$stall->stall_number} was cancelled after the payment deadline.", route('admin.mappings'));
            }
            AuditLog::record("Automatically cancelled reservation #{$lease->id} and invoice #{$invoice->invoice_number}: {$reason}", 'StallMapping', $lease->id);
            return [$lease->user, $message];
        }, 3);

        if (!$cancelled) {
            return false;
        }
        [$tenant, $message] = $cancelled;
        if ($tenant) {
            $this->mailer->sendEmail($tenant->email, $tenant->name, 'Your stall reservation was cancelled', '<p>' . e($message) . '</p>', $message);
        }
        return true;
    }

    public static function calculateContractRent(string $monthlyRate, int $months): string
    {
        [$whole, $fraction] = array_pad(explode('.', $monthlyRate, 2), 2, '0');
        $cents = ((int) $whole * 100 + (int) str_pad($fraction, 2, '0')) * $months;
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Keep a lease in good standing when its monthly rental invoice is paid.
     * Paying never moves end_date; leases are extended only through an approved renewal request.
     */
    public function markGoodStandingOnPayment(Invoice $invoice): ?StallMapping
    {
        $lease = StallMapping::where('user_id', $invoice->user_id)
            ->where('stall_id', $invoice->stall_id)
            ->when($invoice->stall_mapping_id, fn ($q) => $q->whereKey($invoice->stall_mapping_id))
            ->where('is_active', true)
            ->first();

        if ($lease) {
            $lease->update(['payment_status' => 'good_standing']);

            AuditLog::record(
                "Lease #{$lease->id} marked Paid via Invoice #{$invoice->invoice_number}",
                'StallMapping',
                $lease->id
            );
        }

        return $lease;
    }

    /**
     * What the tenant still owes on this lease: pending invoices for the same tenant and stall.
     */
    public function unpaidBalance(StallMapping $lease): float
    {
        return (float) Invoice::where('user_id', $lease->user_id)
            ->where('stall_id', $lease->stall_id)
            ->where('status', 'pending')
            // A reservation's first bill is voided on termination, so it is not owed.
            ->when($lease->isPendingInitial() && $lease->initial_invoice_id, fn ($q) => $q->whereKeyNot($lease->initial_invoice_id))
            ->sum('total_amount');
    }

    public function canTerminate(StallMapping $lease): bool
    {
        return ($lease->is_active || $lease->isPendingInitial()) && $lease->renewal_status !== 'awaiting_payment';
    }

    /**
     * Terminate an active or reserved lease and make the stall available again.
     * Unpaid invoices are kept: the tenant still owes them, so the lease stays past due.
     * An unpaid reservation's initial invoice is cancelled instead: the stall was never occupied.
     *
     * @throws DomainException when the lease has already ended.
     */
    public function terminateLease(StallMapping $lease, string $reason): StallMapping
    {
        if (!$this->canTerminate($lease)) {
            throw new DomainException('This lease has ended or its renewal is awaiting payment.');
        }

        $balance = $this->unpaidBalance($lease);

        DB::transaction(function () use ($lease, $reason, $balance) {
            $lease = StallMapping::whereKey($lease->id)->lockForUpdate()->firstOrFail();
            if (!$this->canTerminate($lease)) {
                throw new DomainException('This lease has ended or its renewal is awaiting payment.');
            }
            if ($lease->isPendingInitial() && $lease->initial_invoice_id) {
                $initial = Invoice::whereKey($lease->initial_invoice_id)->where('status', 'pending')->first();
                if ($initial) {
                    $initial->update([
                        'status' => 'cancelled',
                        'notes' => trim(($initial->notes ? $initial->notes . ' | ' : '') . 'Cancelled: reservation terminated'),
                    ]);
                    AuditLog::record("Cancelled invoice #{$initial->invoice_number} (reservation terminated)", 'Invoice', $initial->id);
                }
            }

            $lease->update([
                'is_active' => false,
                'payment_status' => $balance > 0 ? 'past_due' : 'settled',
                'termination_reason' => $reason,
                'terminated_at' => now(),
            ]);
            // Model update (not a query update) so StallObserver emails the stall's waitlist.
            Stall::find($lease->stall_id)?->update(['status' => 'available']);
        });

        AuditLog::record(
            "Terminated lease mapping #{$lease->id}: {$reason}",
            'StallMapping',
            $lease->id
        );

        $tenant = $lease->user;
        if ($tenant) {
            $stallNumber = $lease->stall?->stall_number;
            $message = "Your lease for Stall {$stallNumber} has been terminated. Reason: {$reason}";
            if ($balance > 0) {
                $message .= ' An unpaid balance of PHP ' . number_format($balance, 2) . ' remains on your account.';
            }
            Notification::send($tenant->id, 'lease', 'Lease terminated', $message, route('user.leases'));
            $this->mailer->sendEmail($tenant->email, $tenant->name, 'Your lease has been terminated', '<p>Hello ' . e($tenant->name) . ',</p><p>' . e($message) . '</p>', $message);
        }

        return $lease;
    }
}
