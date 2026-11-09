<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        protected PayMongoService $payMongoService
    ) {}

    /**
     * Prepare a PayMongo checkout session for one or multiple pending invoices.
     *
     * @param int $userId
     * @param int[] $invoiceIds
     * @param string $successUrl
     * @param string $cancelUrl
     * @return array{success: bool, checkout_url?: ?string, error?: string}
     */
    public function prepareCheckoutSession(
        int $userId,
        array $invoiceIds,
        string $successUrl,
        string $cancelUrl
    ): array {
        if (empty($invoiceIds)) {
            return [
                'success' => false,
                'error' => 'Please select at least one pending invoice to pay.',
            ];
        }

        $invoices = Invoice::with(['stall', 'items'])
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->whereIn('id', $invoiceIds)
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'success' => false,
                'error' => 'Selected invoices are invalid or already paid.',
            ];
        }

        $lineItems = [];
        foreach ($invoices as $inv) {
            $desc = $inv->items->pluck('description')->join(', ') ?: ($inv->notes ?: 'Monthly Stall Rental');
            $lineItems[] = [
                'name' => "Stall {$inv->stall->stall_number} Invoice (#{$inv->invoice_number})",
                'amount' => (float) $inv->total_amount,
                'description' => $desc,
            ];
        }

        $result = $this->payMongoService->createCheckoutSession($lineItems, $successUrl, $cancelUrl);

        if ($result['success'] && !empty($result['checkout_url']) && !empty($result['id'])) {
            DB::transaction(function () use ($invoices, $userId, $result) {
                foreach ($invoices as $inv) {
                    PaymentTransaction::create([
                        'invoice_id' => $inv->id,
                        'user_id' => $userId,
                        'gateway' => 'paymongo',
                        'gateway_transaction_id' => $result['id'],
                        'amount' => $inv->total_amount,
                        'status' => 'pending',
                        'payload' => $result['data'] ?? [],
                    ]);
                }
            });

            return [
                'success' => true,
                'checkout_url' => $result['checkout_url'],
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to initialize payment gateway.',
        ];
    }

    public function reconcilePendingCheckouts(int $userId): int
    {
        $sessionIds = PaymentTransaction::where('user_id', $userId)
            ->where('gateway', 'paymongo')->where('status', 'pending')
            ->whereHas('invoice', fn ($query) => $query->where('status', 'pending'))
            ->whereNotNull('gateway_transaction_id')
            ->distinct()->pluck('gateway_transaction_id');
        $updatedCount = 0;

        foreach ($sessionIds as $sessionId) {
            if (!Cache::add('paymongo:verify:' . $userId . ':' . $sessionId, true, 15)) {
                continue;
            }

            $result = $this->payMongoService->retrieveCheckoutSession($sessionId);
            $session = $result['data'] ?? [];
            if (!($result['success'] ?? false) || ($session['id'] ?? null) !== $sessionId) {
                continue;
            }

            $payments = $session['attributes']['payments'] ?? [];
            $paidPayment = collect($payments)->first(fn ($payment) =>
                ($payment['attributes']['status'] ?? null) === 'paid'
            );
            if (!$paidPayment) {
                continue;
            }

            $updatedCount += $this->handleSuccessfulCheckout(
                $sessionId,
                $paidPayment['attributes']['source']['type'] ?? 'paymongo',
                $session
            );
        }

        return $updatedCount;
    }

    /**
     * Fulfill a paid checkout session from webhook or gateway confirmation.
     */
    public function handleSuccessfulCheckout(
        string $sessionId,
        string $paymentMethod = 'paymongo',
        array $sessionPayload = []
    ): int {
        return DB::transaction(function () use ($sessionId, $paymentMethod, $sessionPayload) {
            $transactions = PaymentTransaction::where('gateway_transaction_id', $sessionId)
                ->where('gateway', 'paymongo')->where('status', 'pending')
                ->orderBy('invoice_id')->lockForUpdate()->get();
            $now = Carbon::now();
            $updatedCount = 0;
            foreach ($transactions as $txn) {
                $invoice = Invoice::whereKey($txn->invoice_id)->lockForUpdate()->first();
                if (!$invoice) {
                    continue;
                }
                $txn->update([
                    'status' => 'successful',
                    'gateway' => $paymentMethod,
                    'paid_at' => $now,
                    'payload' => $sessionPayload ?: $txn->payload,
                ]);

                if ($invoice->status === 'pending') {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => $now,
                    ]);
                    app(InvoiceService::class)->onInvoicePaid($invoice);
                } elseif ($invoice->status === 'cancelled') {
                    $message = "Payment was confirmed for cancelled invoice {$invoice->invoice_number}. Contact the office to review the payment; it does not reactivate a cancelled reservation.";
                    Notification::send($invoice->user_id, 'warning', 'Payment requires review', $message, route('user.payments'));
                    foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
                        Notification::send($adminId, 'warning', 'Payment received for cancelled invoice', $message, route('admin.payments'));
                    }
                }

                AuditLog::record(
                    "Payment confirmed via {$paymentMethod} for Invoice #{$txn->invoice_id}",
                    'Invoice',
                    $txn->invoice_id,
                    null,
                    $txn->user_id
                );

                $updatedCount++;
            }
            return $updatedCount;
        }, 3);
    }
}
