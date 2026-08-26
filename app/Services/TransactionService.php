<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
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

    /**
     * Fulfill a paid checkout session from webhook or gateway confirmation.
     */
    public function handleSuccessfulCheckout(
        string $sessionId,
        string $paymentMethod = 'paymongo',
        array $sessionPayload = []
    ): int {
        $transactions = PaymentTransaction::where('gateway_transaction_id', $sessionId)
            ->where('status', '!=', 'successful')
            ->get();

        if ($transactions->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $updatedCount = 0;

        DB::transaction(function () use ($transactions, $paymentMethod, $sessionPayload, $now, &$updatedCount) {
            foreach ($transactions as $txn) {
                $txn->update([
                    'status' => 'successful',
                    'gateway' => $paymentMethod,
                    'paid_at' => $now,
                    'payload' => $sessionPayload ?: $txn->payload,
                ]);

                Invoice::where('id', $txn->invoice_id)->update([
                    'status' => 'paid',
                    'paid_at' => $now,
                ]);

                AuditLog::record(
                    "Payment confirmed via {$paymentMethod} for Invoice #{$txn->invoice_id}",
                    'Invoice',
                    $txn->invoice_id,
                    null,
                    $txn->user_id
                );

                $updatedCount++;
            }
        });

        return $updatedCount;
    }
}
