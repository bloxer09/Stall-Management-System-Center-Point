<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendBulkSms implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(public array $recipients, public string $message) {}

    public function handle(SmsService $sms): void
    {
        foreach ($this->recipients as $recipient) {
            try {
                if (!$sms->sendSms($recipient['phone'], $this->message)) {
                    Log::warning('Announcement SMS was not sent.', ['user_id' => $recipient['id']]);
                }
            } catch (\Throwable $e) {
                Log::warning('Announcement SMS failed.', ['user_id' => $recipient['id'], 'error' => $e->getMessage()]);
            }
        }
    }
}
