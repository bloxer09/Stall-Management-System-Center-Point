<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    protected Client $client;
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paymongo.secret_key') ?? env('PAYMONGO_SECRET_KEY', '');
        $this->client = new Client([
            'base_uri' => 'https://api.paymongo.com/v1/',
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->secretKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function createCheckoutSession(array $items, string $successUrl, string $cancelUrl, ?string $description = null): array
    {
        try {
            $lineItems = [];
            foreach ($items as $item) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int) round($item['amount'] * 100), // convert to centavos
                    'description' => $item['description'] ?? 'Stall Invoice Payment',
                    'name' => $item['name'] ?? 'Stall Rental Invoice',
                    'quantity' => 1,
                ];
            }

            $payload = [
                'data' => [
                    'attributes' => [
                        'billing' => null,
                        'send_email_receipt' => true,
                        'show_description' => true,
                        'show_line_items' => true,
                        'cancel_url' => $cancelUrl,
                        'success_url' => $successUrl,
                        'description' => $description ?? 'STALLM Invoices Payment',
                        'line_items' => $lineItems,
                        'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    ]
                ]
            ];

            $response = $this->client->post('checkout_sessions', [
                'json' => $payload
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return [
                'success' => true,
                'id' => $body['data']['id'] ?? null,
                'checkout_url' => $body['data']['attributes']['checkout_url'] ?? null,
                'data' => $body['data'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('PayMongo Checkout Session error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        try {
            $response = $this->client->get('checkout_sessions/' . rawurlencode($sessionId), [
                'connect_timeout' => 3,
                'timeout' => 5,
            ]);
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            return ['success' => true, 'data' => $body['data'] ?? []];
        } catch (\Exception $e) {
            Log::warning('PayMongo checkout verification failed.', ['session_id' => $sessionId]);

            return ['success' => false];
        }
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if (empty($signatureHeader) || empty($secret)) {
            return false;
        }

        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $testSignature = null;
        $liveSignature = null;

        foreach ($parts as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                if ($kv[0] === 't') $timestamp = $kv[1];
                if ($kv[0] === 'te') $testSignature = $kv[1];
                if ($kv[0] === 'li') $liveSignature = $kv[1];
            }
        }

        $expectedSig = $testSignature ?? $liveSignature;
        if (!$timestamp || !$expectedSig) {
            return false;
        }

        $comparisonString = $timestamp . '.' . $payload;
        $calculatedSig = hash_hmac('sha256', $comparisonString, $secret);

        return hash_equals($calculatedSig, $expectedSig);
    }
}
