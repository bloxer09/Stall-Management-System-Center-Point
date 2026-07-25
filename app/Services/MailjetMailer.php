<?php

namespace App\Services;

use \Mailjet\Client as MailjetClient;
use \Mailjet\Resources;
use Illuminate\Support\Facades\Log;

class MailjetMailer
{
    protected ?MailjetClient $client = null;
    protected string $fromEmail;
    protected string $fromName;

    public function __construct()
    {
        $apiKey = config('services.mailjet.key') ?? env('MAILJET_API_KEY', '');
        $apiSecret = config('services.mailjet.secret') ?? env('MAILJET_API_SECRET', '');
        $this->fromEmail = config('services.mailjet.from_email') ?? env('MAILJET_FROM_EMAIL', 'no-reply@stallm.com');
        $this->fromName = config('services.mailjet.from_name') ?? 'STALLM System';

        if (!empty($apiKey) && !empty($apiSecret)) {
            $this->client = new MailjetClient($apiKey, $apiSecret, true, ['version' => 'v3.1']);
        }
    }

    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        if (!$this->client) {
            Log::warning('Mailjet client not configured. Email suppressed to: ' . $toEmail);
            return false;
        }

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->fromEmail,
                        'Name' => $this->fromName
                    ],
                    'To' => [
                        [
                            'Email' => $toEmail,
                            'Name' => $toName
                        ]
                    ],
                    'Subject' => $subject,
                    'TextPart' => $textBody ?: strip_tags($htmlBody),
                    'HTMLPart' => $htmlBody,
                ]
            ]
        ];

        try {
            $response = $this->client->post(Resources::$Email, ['body' => $body]);
            if ($response->success()) {
                return true;
            }
            Log::error('Mailjet error response: ' . json_encode($response->getData()));
            return false;
        } catch (\Exception $e) {
            Log::error('Mailjet exception: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOtp(string $toEmail, string $toName, string $otpCode): bool
    {
        $subject = "Your STALLM Verification Code";
        $htmlBody = "
            <div style='font-family: sans-serif; padding: 20px;'>
                <h2>Verification Code</h2>
                <p>Hello {$toName},</p>
                <p>Your one-time security code is:</p>
                <h1 style='color: #4F46E5; letter-spacing: 4px;'>{$otpCode}</h1>
                <p>This code will expire in 10 minutes. Do not share this code with anyone.</p>
            </div>
        ";
        return $this->sendEmail($toEmail, $toName, $subject, $htmlBody);
    }
}
