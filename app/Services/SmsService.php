<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $apiKey;
    protected string $senderName;
    protected Client $client;

    public function __construct()
    {
        $this->apiKey = config('services.semaphore.api_key') ?? env('SEMAPHORE_API_KEY', '');
        $this->senderName = config('services.semaphore.sender_name') ?? env('SEMAPHORE_SENDER_NAME', 'STALLM');
        $this->client = new Client(['base_uri' => 'https://api.semaphore.co/api/v4/']);
    }

    public function sendSms(string $number, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('Semaphore API key not configured. SMS suppressed to: ' . $number);
            return false;
        }

        try {
            $response = $this->client->post('messages', [
                'form_params' => [
                    'apikey' => $this->apiKey,
                    'number' => $number,
                    'message' => $message,
                    'sendername' => $this->senderName,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            return $statusCode >= 200 && $statusCode < 300;
        } catch (\Exception $e) {
            Log::error('Semaphore SMS Error: ' . $e->getMessage());
            return false;
        }
    }
}
