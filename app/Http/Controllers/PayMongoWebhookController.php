<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Services\PayMongoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayMongoWebhookController extends Controller
{
    public function handleWebhook(Request $request, PayMongoService $payMongoService): JsonResponse
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Paymongo-Signature', '');
        $webhookSecret = config('services.paymongo.webhook_secret') ?? env('PAYMONGO_WEBHOOK_TOKEN', '');

        if (empty($webhookSecret)) {
            Log::error('PayMongo Webhook failed: Webhook secret is not configured.');
            return response()->json(['error' => 'Webhook secret configuration missing'], 400);
        }

        $isValid = $payMongoService->verifyWebhookSignature($payload, $signatureHeader, $webhookSecret);
        if (!$isValid) {
            Log::warning('PayMongo Webhook signature verification failed.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true);
        $eventType = $event['data']['attributes']['type'] ?? null;

        if ($eventType === 'checkout_session.payment.paid') {
            $session = $event['data']['attributes']['data'] ?? [];
            $sessionId = $session['id'] ?? null;
            $paymentMethod = $session['attributes']['payment_method_used'] ?? 'paymongo';

            if ($sessionId) {
                $transactions = PaymentTransaction::where('gateway_transaction_id', $sessionId)->get();

                foreach ($transactions as $txn) {
                    $txn->update([
                        'status' => 'successful',
                        'gateway' => $paymentMethod,
                        'paid_at' => now(),
                        'payload' => $session,
                    ]);

                    Invoice::where('id', $txn->invoice_id)->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }
}
