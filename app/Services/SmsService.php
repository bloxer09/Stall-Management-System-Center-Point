<?php

namespace App\Services;

use App\Mail\GenericNotificationMail;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    /**
     * Determine if current environment/request is local development.
     */
    public function isLocal(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        if (app()->environment('local', 'testing')) {
            return true;
        }

        $host = '';
        if (app()->runningInConsole()) {
            $appUrl = config('app.url', 'http://localhost');
            $host = parse_url($appUrl, PHP_URL_HOST) ?? '';
        } else {
            $host = request()->getHost() ?? '';
        }

        return in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
    }

    /**
     * Dispatch SMS with automatic local catcher and production Semaphore gateway routing.
     */
    public function sendSms(string $number, string $message, bool $essential = false): bool
    {
        // System Settings can turn SMS off; essential texts (admin 2FA codes) still go out,
        // otherwise admins with two-factor on could never sign in.
        if (!$essential && !$this->enabled()) {
            return false;
        }

        // 1. Local Development: Laravel SMS Catcher + Mailpit + Logs
        if ($this->isLocal()) {
            return $this->captureLocalSms($number, $message);
        }

        // 2. Production: Dispatch via Semaphore SMS Gateway
        return $this->sendViaSemaphore($number, $message);
    }

    /**
     * The System Settings switch. If settings cannot be read (fresh install, no table yet),
     * SMS stays on as it always was.
     */
    public function enabled(): bool
    {
        try {
            return Setting::get('sms_enabled', '1') === '1';
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Capture SMS into local JSON storage (for /sms-catcher dashboard), Mailpit, and logs.
     */
    protected function captureLocalSms(string $number, string $message): bool
    {
        $now = Carbon::now();
        $messageId = (string) Str::uuid();

        // 1. Write to storage/logs/sms-catcher.json (for michal78/laravel-sms-catcher dashboard at /sms-catcher)
        try {
            $storagePath = config('sms-catcher.storage_path', storage_path('logs/sms-catcher.json'));
            $directory = dirname($storagePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $messages = [];
            if (file_exists($storagePath)) {
                $raw = file_get_contents($storagePath);
                $messages = json_decode($raw, true) ?: [];
            }

            $messages[] = [
                'id' => $messageId,
                'to' => $number,
                'from' => $this->senderName,
                'body' => $message,
                'notification' => 'App\\Services\\SmsService',
                'timestamp' => $now->toIso8601String(),
                'read_at' => null,
                'extra' => [],
            ];

            file_put_contents($storagePath, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            Log::warning('Failed to write to sms-catcher.json: ' . $e->getMessage());
        }

        // 2. Output formatted entry to laravel.log
        Log::info("📱 [LOCAL SMS CATCHER] Captured SMS to {$number} [From: {$this->senderName}]: \"{$message}\"");

        // 3. Dispatch an SMS Preview to Mailpit so it is visible in the Mailpit inbox (127.0.0.1:1025)
        try {
            $subject = "📱 [SMS Catcher] To: {$number}";
            $htmlBody = "
                <div style='font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; background-color: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;'>
                    <div style='background-color: #0f172a; color: #ffffff; padding: 12px 16px; border-radius: 10px 10px 0 0; font-size: 13px; font-weight: bold;'>
                        <span>📱 Local SMS Catcher</span> &bull; <span style='color: #94a3b8;'>From: {$this->senderName}</span>
                    </div>
                    <div style='background-color: #ffffff; padding: 18px; border-radius: 0 0 10px 10px; border: 1px solid #e2e8f0; border-top: none;'>
                        <p style='margin: 0 0 8px 0; color: #64748b; font-size: 12px;'>Recipient Phone: <strong style='color: #0f172a;'>{$number}</strong></p>
                        <div style='background-color: #f1f5f9; color: #0f172a; padding: 14px; border-radius: 10px; font-size: 14px; line-height: 1.5; border-left: 4px solid #3b82f6;'>
                            " . nl2br(htmlspecialchars($message)) . "
                        </div>
                        <p style='margin: 12px 0 0 0; color: #94a3b8; font-size: 11px;'>
                            Captured at {$now->toDateTimeString()}. You can also view this in the SMS Dashboard at 
                            <a href='http://127.0.0.1:8000/sms-catcher' style='color: #3b82f6; font-weight: bold;'>/sms-catcher</a>.
                        </p>
                    </div>
                </div>
            ";
            
            Mail::mailer('mailpit')->to('sms-catcher@stallm.local', "SMS to {$number}")->send(
                new GenericNotificationMail($subject, $htmlBody, $message)
            );
        } catch (\Throwable $e) {
            // Local mailpit failure is non-fatal for SMS catcher
            Log::debug('SMS Catcher Mailpit preview dispatch skipped: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Send SMS through Semaphore API v4 in production environment.
     */
    protected function sendViaSemaphore(string $number, string $message): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('Semaphore API key not configured in production. SMS suppressed to: ' . $number);
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
        } catch (\Throwable $e) {
            Log::error('Semaphore SMS Gateway Error: ' . $e->getMessage(), [
                'exception' => $e,
                'recipient' => $number,
            ]);
            return false;
        }
    }
}
