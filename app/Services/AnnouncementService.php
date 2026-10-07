<?php

namespace App\Services;

use App\Jobs\SendBulkEmail;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnnouncementService
{
    public function __construct(protected MailService $mailer) {}

    /**
     * Tenants with an active lease, optionally only on one floor. Each tenant appears once.
     *
     * @return Collection<int, User>
     */
    public function recipients(?int $locationId): Collection
    {
        return User::where('role', 'user')
            ->whereNull('deactivated_at')
            ->whereHas('mappings', function ($q) use ($locationId) {
                $q->where('is_active', true);
                if ($locationId) {
                    $q->whereHas('stall', fn ($s) => $s->where('location_id', $locationId));
                }
            })
            ->get();
    }

    /**
     * Save the announcement, then ring every recipient's bell (and email them when asked).
     *
     * @param  array{title: string, body: string, stall_location_id: ?int, emailed: bool}  $data
     */
    public function post(array $data, User $admin): Announcement
    {
        $announcement = Announcement::create($data + ['posted_by' => $admin->id]);
        $recipients = $this->recipients($announcement->stall_location_id);
        $preview = Str::limit($announcement->body, 140);

        foreach ($recipients as $tenant) {
            Notification::send($tenant->id, 'announcement', $announcement->title, $preview, route('user.dashboard'));
        }

        if ($announcement->emailed && $recipients->isNotEmpty()) {
            SendBulkEmail::dispatch(
                $recipients->map(fn ($tenant) => ['email' => $tenant->email, 'name' => $tenant->name])->values()->all(),
                $announcement->title,
                '<p>Hello {name},</p><p>' . nl2br(e($announcement->body)) . '</p>',
                $announcement->body,
            )->afterResponse();
        }

        AuditLog::record("Posted announcement \"{$announcement->title}\" to {$recipients->count()} tenant(s)", 'Announcement', $announcement->id);

        return $announcement;
    }
}
