<?php

namespace App\Services;

use App\Jobs\SendBulkEmail;
use App\Jobs\SendBulkSms;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AnnouncementService
{
    public function __construct(protected MailService $mailer, protected SmsService $sms) {}

    public function smsRecipients(?int $locationId): Collection
    {
        return $this->recipients($locationId)->filter(fn ($tenant) => filled($tenant->phone));
    }

    public function smsEnabled(): bool
    {
        return $this->sms->enabled();
    }

    public function smsMessage(Announcement $announcement): string
    {
        $link = route('user.dashboard');
        $text = preg_replace('/\s+/u', ' ', trim($announcement->title . ': ' . $announcement->body));
        $budget = max(0, 320 - mb_strlen($link) - 1);
        $preview = mb_strlen($text) > $budget ? mb_substr($text, 0, max(0, $budget - 1)) . '…' : $text;
        return $preview . ' ' . $link;
    }

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
     * @param  array{title: string, body: string, stall_location_id: ?int, emailed: bool, sms_requested?: bool}  $data
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

        if ($announcement->sms_requested && $this->sms->enabled()) {
            $phones = $recipients->filter(fn ($tenant) => filled($tenant->phone));
            if ($phones->isNotEmpty()) {
                SendBulkSms::dispatch(
                    $phones->map(fn ($tenant) => ['id' => $tenant->id, 'phone' => $tenant->phone])->values()->all(),
                    $this->smsMessage($announcement),
                )->afterResponse();
            }
        }

        AuditLog::record("Posted announcement \"{$announcement->title}\" to {$recipients->count()} tenant(s)", 'Announcement', $announcement->id);

        return $announcement;
    }
}
