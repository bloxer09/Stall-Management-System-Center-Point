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

        // Prevent duplicate invoices for the same stall and billing period
        $alreadyInvoiced = Invoice::where('stall_id', $lease->stall_id)
            ->where('billing_period', $billingPeriod)
            ->exists();

        if ($alreadyInvoiced) {
            return null;
        }

        return DB::transaction(function () use ($lease, $billingPeriod) {
            $periodDate = Carbon::createFromFormat('Y-m', $billingPeriod) ?: Carbon::now();
            $invoiceNumber = 'INV-' . $periodDate->format('Ym') . '-' . str_pad((string) $lease->stall_id, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $lease->user_id,
                'stall_id' => $lease->stall_id,
                'total_amount' => $lease->stall->price,
                'status' => 'pending',
                'due_date' => $periodDate->copy()->startOfMonth()->addDays(5)->toDateString(),
                'billing_period' => $billingPeriod,
                'notes' => 'Monthly Stall Rental - ' . $periodDate->format('F Y'),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Monthly Stall Rental - ' . $periodDate->format('F Y'),
                'amount' => $lease->stall->price,
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
     * Record a manual cash payment received at the office.
     */
    public function recordManualCashPayment(
        int $userId,
        int $stallId,
        float $amount,
        string $reference,
        ?string $notes = null
    ): Invoice {
        return DB::transaction(function () use ($userId, $stallId, $amount, $reference, $notes) {
            $invoiceNumber = 'INV-CASH-' . time();

            $invoice = Invoice::create([
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
                'invoice_id' => $invoice->id,
                'description' => $notes ?: 'Office Cash Payment',
                'amount' => $amount,
                'item_type' => 'rent',
            ]);

            PaymentTransaction::create([
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'gateway' => 'cash',
                'gateway_transaction_id' => $reference,
                'amount' => $amount,
                'status' => 'successful',
                'paid_at' => Carbon::now(),
            ]);

            AuditLog::record(
                "Recorded manual cash payment of ₱" . number_format($amount, 2) . " for Invoice #{$invoice->invoice_number}",
                'Invoice',
                $invoice->id
            );

            return $invoice;
        });
    }

    /**
     * Mark an existing pending invoice as paid (e.g. from admin panel).
     */
    public function markAsPaid(Invoice $invoice, string $gateway = 'office_cash', ?string $reference = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $gateway, $reference) {
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

            return $invoice;
        });
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
