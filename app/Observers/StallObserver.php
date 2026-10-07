<?php

namespace App\Observers;

use App\Models\Stall;
use App\Services\MailService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Throwable;

// After commit: a rolled-back termination must not email anyone.
class StallObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(protected MailService $mailer) {}

    /**
     * When a stall opens up (any path: termination, admin edit), email everyone waiting for it once.
     */
    public function updated(Stall $stall): void
    {
        if (!$stall->wasChanged('status') || $stall->status !== 'available') {
            return;
        }

        $link = url('/stalls');
        foreach ($stall->waitlist()->whereNull('notified_at')->get() as $entry) {
            try {
                $this->mailer->sendEmail(
                    $entry->email,
                    $entry->name,
                    "Stall {$stall->stall_number} is now available",
                    '<p>Hello ' . e($entry->name) . ',</p>'
                        . '<p>Good news: <strong>Stall ' . e($stall->stall_number) . '</strong> is now open for applications.</p>'
                        . '<p><a href="' . e($link) . '">Browse stalls and apply</a> before someone else does.</p>',
                    "Hello {$entry->name}, Stall {$stall->stall_number} is now open for applications. Apply at {$link}"
                );
            } catch (Throwable $e) {
                // A mail failure must never block the stall update.
                report($e);
            }

            $entry->forceFill(['notified_at' => now()])->save();
        }
    }
}
