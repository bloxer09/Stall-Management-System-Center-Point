<?php

namespace App\Services;

use App\Config\App;

class SmsService
{
    /**
     * Send an SMS message using the configured provider.
     *
     * @param string $phone The recipient's phone number.
     * @param string $message The text message to send.
     * @return array Status of the send operation ['sent' => bool, 'error' => ?string, 'notice' => ?string]
     */
    public function send(string $phone, string $message): array
    {
        $provider = strtolower(trim((string) App::env('SMS_PROVIDER', 'log')));

        if ($provider === '' || $provider === 'log') {
            error_log(sprintf('[SMS LOG] Sending message to %s: "%s"', $phone, $message));
            return [
                'sent' => true,
                'notice' => 'SMS sent via php error log because SMS_PROVIDER is "log" or empty.',
            ];
        }

        if ($provider === 'semaphore') {
            $apiKey = trim((string) App::env('SEMAPHORE_API_KEY', ''));
            if ($apiKey === '') {
                error_log('Semaphore SMS sending failed: SEMAPHORE_API_KEY is not configured in .env');
                return [
                    'sent' => false,
                    'error' => 'Semaphore API key is missing.',
                ];
            }

            $senderName = trim((string) App::env('SEMAPHORE_SENDER_NAME', ''));
            
            return $this->sendViaSemaphore($apiKey, $phone, $message, $senderName);
        }

        error_log(sprintf('Unsupported SMS provider configured: %s', $provider));
        return [
            'sent' => false,
            'error' => sprintf('Unsupported SMS provider: %s', $provider),
        ];
    }

    /**
     * Send SMS via Semaphore API.
     */
    private function sendViaSemaphore(string $apiKey, string $phone, string $message, string $senderName): array
    {
        $url = 'https://semaphore.co/api/v4/messages';
        
        $params = [
            'apikey' => $apiKey,
            'number' => $phone,
            'message' => $message,
        ];

        if ($senderName !== '') {
            $params['sendername'] = $senderName;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("Semaphore API connection failed: " . $curlError);
            return [
                'sent' => false,
                'error' => 'Failed to connect to Semaphore API: ' . $curlError,
            ];
        }

        $result = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            // Semaphore usually returns a JSON array of message objects on success.
            if (is_array($result) && isset($result[0]['status'])) {
                return ['sent' => true];
            }
            
            // Check if it's an error format
            if (is_array($result) && isset($result['error'])) {
                error_log("Semaphore API returned error message: " . json_encode($result));
                return [
                    'sent' => false,
                    'error' => $result['error'],
                ];
            }

            return ['sent' => true];
        }

        error_log(sprintf("Semaphore API error: HTTP status %d, Response: %s", $httpCode, $response));
        return [
            'sent' => false,
            'error' => sprintf('Semaphore API error. HTTP Code: %d', $httpCode),
        ];
    }
}
