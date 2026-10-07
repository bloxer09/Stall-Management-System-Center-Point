<?php

namespace App\Observers;

use App\Jobs\SendBulkEmail;
use App\Models\Stall;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

// After commit: a rolled-back termination must not email anyone.
class StallObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * When a stall opens up (any path: termination, admin edit), email everyone waiting for it.
     * Each entry is stamped notified only when its email is delivered; a failed one is tried
     * again the next time the stall opens.
     */
    public function updated(Stall $stall): void
    {
        if (!$stall->wasChanged('status') || $stall->status !== 'available') {
            return;
        }

        $recipients = $stall->waitlist()->whereNull('notified_at')->get()
            ->map(fn ($entry) => ['email' => $entry->email, 'name' => $entry->name, 'waitlist_id' => $entry->id])
            ->values()->all();
        if ($recipients === []) {
            return;
        }

        $link = url('/stalls');
        SendBulkEmail::dispatch(
            $recipients,
            "Stall {$stall->stall_number} is now available",
            '<p>Hello {name},</p>'
                . '<p>Good news: <strong>Stall ' . e($stall->stall_number) . '</strong> is now open for applications.</p>'
                . '<p><a href="' . e($link) . '">Browse stalls and apply</a> before someone else does.</p>',
            "Hello {name}, Stall {$stall->stall_number} is now open for applications. Apply at {$link}"
        )->afterResponse();
    }
}
