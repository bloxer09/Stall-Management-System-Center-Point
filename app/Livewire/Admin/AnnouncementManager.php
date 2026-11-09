<?php

namespace App\Livewire\Admin;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\StallLocation;
use App\Services\AnnouncementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementManager extends Component
{
    use WithPagination;

    public string $title = '';
    public string $body = '';
    public ?int $locationId = null;
    public bool $sendEmail = false;
    public bool $sendSms = false;
    public bool $showCreateModal = false;

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function post(AnnouncementService $announcements): void
    {
        $this->validate([
            'title' => 'required|string|max:150',
            'body' => 'required|string|max:2000',
            'locationId' => 'nullable|exists:stall_locations,id',
        ]);

        $announcement = $announcements->post([
            'title' => trim($this->title),
            'body' => trim($this->body),
            'stall_location_id' => $this->locationId ?: null,
            'emailed' => $this->sendEmail,
            'sms_requested' => $this->sendSms && $announcements->smsEnabled(),
        ], Auth::user());

        $count = $announcements->recipients($announcement->stall_location_id)->count();
        $message = "Announcement posted for {$count} tenant(s).";
        if ($this->sendSms) {
            $eligible = $announcements->smsRecipients($announcement->stall_location_id)->count();
            $skipped = $count - $eligible;
            $message .= $announcements->smsEnabled()
                ? " SMS requested for {$eligible}; {$skipped} skipped without a phone number."
                : ' SMS is disabled in system settings; no SMS requested.';
        }
        $this->reset(['title', 'body', 'locationId', 'sendEmail', 'sendSms']);
        $this->showCreateModal = false;
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function delete(int $id): void
    {
        $announcement = Announcement::findOrFail($id);
        AuditLog::record("Deleted announcement \"{$announcement->title}\"", 'Announcement', $announcement->id);
        $announcement->delete();
        $this->dispatch('notify', type: 'success', message: 'Announcement deleted.');
    }

    public function render()
    {
        $service = app(AnnouncementService::class);
        $recipients = $service->recipients($this->locationId);
        $smsEligible = $recipients->filter(fn ($tenant) => filled($tenant->phone))->count();
        return view('livewire.admin.announcement-manager', [
            'smsEnabled' => $service->smsEnabled(),
            'smsEligible' => $smsEligible,
            'smsSkipped' => $recipients->count() - $smsEligible,
            'announcements' => Announcement::with(['location', 'poster'])->latest()->paginate(10),
            'locations' => StallLocation::orderBy('sort_order')->get(),
        ]);
    }
}
