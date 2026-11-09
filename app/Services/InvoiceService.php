<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentTransaction;
use App\Models\StallMapping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate a monthly rental invoice for a specific active lease.
     */
    public function generateMonthlyRentalInvoice(StallMapping $lease, ?string $billingPeriod = null): ?Invoice
    {
        $billingPeriod = $billingPeriod ?: Carbon::now()->format('Y-m');

        $lease->loadMissing('stall');
        if (!$lease->stall) {
            return null;
        }

        return DB::transaction(function () use ($lease, $billingPeriod) {
            $lease = StallMapping::with('stall')->whereKey($lease->id)->lockForUpdate()->firstOrFail();
            $periodDate = Carbon::createFromFormat('!Y-m', $billingPeriod);
            $coveredByRenewal = Invoice::where('stall_mapping_id', $lease->id)
                ->where('is_renewal', true)->where('status', 'paid')
                ->get()->contains(fn ($invoice) => $periodDate->gte($invoice->rent_period_start)
                    && $periodDate->lt($invoice->rent_period_end));
            if ($coveredByRenewal) {
                return null;
            }
            if ($lease->rent_billing_mode === 'upfront') {
                if (!$periodDate || $periodDate->lt($lease->start_date->copy()->startOfMonth())
                    || $periodDate->gte($lease->end_date)
                    || ($lease->upfront_covered_until && $periodDate->lt($lease->upfront_covered_until))) {
                    return null;
                }
            }
            $alreadyInvoiced = Invoice::where('billing_period', $billingPeriod)
                ->where(function ($q) use ($lease) {
                    $q->where('stall_mapping_id', $lease->id)
                        ->orWhere(fn ($legacy) => $legacy->whereNull('stall_mapping_id')
                            ->where('stall_id', $lease->stall_id)->where('user_id', $lease->user_id)
                            ->where('notes', 'like', 'Monthly Stall Rental - %'));
                })->exists();
            if ($alreadyInvoiced) {
                return null;
            }
            $periodDate = $periodDate ?: Carbon::now();
            $monthlyRate = $lease->agreed_monthly_rate ?? $lease->stall->price;
            $invoiceNumber = 'INV-' . $periodDate->format('Ym') . '-' . str_pad((string) $lease->stall_id, 4, '0', STR_PAD_LEFT);
            if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber .= '-L' . $lease->id;
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $lease->user_id,
                'stall_id' => $lease->stall_id,
                'stall_mapping_id' => $lease->id,
                'total_amount' => $monthlyRate,
                'status' => 'pending',
                'due_date' => $periodDate->copy()->startOfMonth()->addDays(5)->toDateString(),
                'billing_period' => $billingPeriod,
                'notes' => 'Monthly Stall Rental - ' . $periodDate->format('F Y'),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Monthly Stall Rental - ' . $periodDate->format('F Y'),
                'amount' => $monthlyRate,
                'item_type' => 'rent',
            ]);

            return $invoice;
        });
    }

    /**
     * Automatically generate monthly rental invoices for all active leases.
     */
    public function generateAllMonthlyInvoices(?string $billingPeriod = null): int
    {
        $activeLeases = StallMapping::with('stall')
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($activeLeases as $lease) {
            $invoice = $this->generateMonthlyRentalInvoice($lease, $billingPeriod);
            if ($invoice) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Trigger automatic lease activation or renewal upon invoice payment confirmation.
     */
    public function onInvoicePaid(Invoice $invoice): void
    {
        if ($invoice->is_renewal) {
            app(LeaseService::class)->finalizePaidRenewal($invoice);
            return;
        }
        // 1. If this invoice is the initial invoice of a pending lease, activate it
        $pendingLease = StallMapping::where('initial_invoice_id', $invoice->id)
            ->where('payment_status', 'pending_initial')
            ->first();

        if ($pendingLease) {
            app(LeaseService::class)->activateLeaseOnPayment($pendingLease);
            return;
        }

        // 2. If it's a recurring monthly invoice for an active lease, keep it in good standing
        app(LeaseService::class)->markGoodStandingOnPayment($invoice);
    }

    /**
     * Record a manual cash payment received at the office.
     */
    public function recordManualCashPayment(
        int $userId,
        int $stallId,
        float $amount,
        string $reference,
        ?string $notes = null
    ): Invoice {
        $invoice = DB::transaction(function () use ($userId, $stallId, $amount, $reference, $notes) {
            $invoiceNumber = 'INV-CASH-' . time();

            $inv = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $userId,
                'stall_id' => $stallId,
                'total_amount' => $amount,
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'due_date' => Carbon::today()->toDateString(),
                'billing_period' => Carbon::now()->format('Y-m'),
                'notes' => $notes,
            ]);

            InvoiceItem::create([
                'invoice_id' => $inv->id,
                'description' => $notes ?: 'Office Cash Payment',
                'amount' => $amount,
                'item_type' => 'rent',
            ]);

            PaymentTransaction::create([
                'invoice_id' => $inv->id,
                'user_id' => $userId,
                'gateway' => 'cash',
                'gateway_transaction_id' => $reference,
                'amount' => $amount,
                'status' => 'successful',
                'paid_at' => Carbon::now(),
            ]);

            AuditLog::record(
                "Recorded manual cash payment of ₱" . number_format($amount, 2) . " for Invoice #{$inv->invoice_number}",
                'Invoice',
                $inv->id
            );

            return $inv;
        });

        $this->onInvoicePaid($invoice);

        return $invoice;
    }

    /**
     * Mark an existing pending invoice as paid (e.g. from admin panel).
     */
    public function markAsPaid(Invoice $invoice, string $gateway = 'office_cash', ?string $reference = null): Invoice
    {
        DB::transaction(function () use (&$invoice, $gateway, $reference) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status !== 'pending') {
                return;
            }
            $invoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
            ]);

            PaymentTransaction::create([
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'gateway' => $gateway,
                'gateway_transaction_id' => $reference ?: ('OFFICE-' . time()),
                'amount' => $invoice->total_amount,
                'status' => 'successful',
                'paid_at' => Carbon::now(),
            ]);

            AuditLog::record(
                "Marked invoice #{$invoice->invoice_number} as paid via {$gateway}",
                'Invoice',
                $invoice->id
            );
            $this->onInvoicePaid($invoice);
        });

        $fresh = $invoice->fresh();

        return $fresh;
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Invoice $invoice, ?string $reason = null): Invoice
    {
        $notes = $invoice->notes;
        if ($reason) {
            $notes = trim(($notes ? $notes . ' | ' : '') . "Cancelled: {$reason}");
        }

        $invoice->update([
            'status' => 'cancelled',
            'notes' => $notes,
        ]);

        AuditLog::record(
            "Cancelled invoice #{$invoice->invoice_number}",
            'Invoice',
            $invoice->id
        );

        return $invoice;
    }
}
