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
        ], Auth::user());

        $count = $announcements->recipients($announcement->stall_location_id)->count();
        $this->reset(['title', 'body', 'locationId', 'sendEmail']);
        $this->resetPage();
        $this->dispatch('notify', type: 'success', message: "Announcement sent to {$count} tenant(s).");
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
        return view('livewire.admin.announcement-manager', [
            'announcements' => Announcement::with(['location', 'poster'])->latest()->paginate(10),
            'locations' => StallLocation::orderBy('sort_order')->get(),
        ]);
    }
}
