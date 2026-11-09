<?php

namespace App\Jobs;

use App\Models\StallWaitlist;
use App\Services\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Sends one email per recipient outside the request (dispatched afterResponse), so posting an
 * announcement or freeing a stall does not wait on N SMTP calls.
 */
class SendBulkEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    /**
     * @param  list<array{email: string, name: string, waitlist_id?: int}>  $recipients
     * @param  string  $html  may contain {name}, replaced with the escaped recipient name
     * @param  string  $text  may contain {name}, replaced with the plain recipient name
     */
    public function __construct(
        public array $recipients,
        public string $subject,
        public string $html,
        public string $text,
    ) {}

    public function handle(MailService $mailer): void
    {
        foreach ($this->recipients as $recipient) {
            $sent = $mailer->sendEmail(
                $recipient['email'],
                $recipient['name'],
                $this->subject,
                str_replace('{name}', e($recipient['name']), $this->html),
                str_replace('{name}', $recipient['name'], $this->text),
            );

            // A waitlist entry counts as notified only once its email really went out.
            if ($sent && isset($recipient['waitlist_id'])) {
                StallWaitlist::whereKey($recipient['waitlist_id'])->update(['notified_at' => now()]);
            }
        }
    }
}
