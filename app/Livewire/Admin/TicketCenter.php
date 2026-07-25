<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TicketCenter extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $statusFilter = '';

    // Selected Ticket Chat Widget State
    public $selectedTicketId = null;
    public $showThreadModal = false; // kept for backwards compatibility or full view
    public $isWidgetMinimized = false;

    // Chat Message Inputs
    public $newMessage = '';
    public $attachment = null;
    public $replyToMessageId = null;

    public function openThreadModal($id)
    {
        $this->resetValidation();
        $this->selectedTicketId = $id;
        $this->showThreadModal = true;
        $this->isWidgetMinimized = false;
        $this->markAsRead();
    }

    public function toggleWidgetMinimize()
    {
        $this->isWidgetMinimized = !$this->isWidgetMinimized;
    }

    public function closeThreadModal()
    {
        $this->showThreadModal = false;
        $this->selectedTicketId = null;
        $this->isWidgetMinimized = false;
        $this->reset(['newMessage', 'attachment', 'replyToMessageId']);
    }

    public function markAsRead()
    {
        if (!$this->selectedTicketId) return;

        $lastMessage = TicketMessage::where('ticket_id', $this->selectedTicketId)->latest()->first();

        TicketParticipant::updateOrCreate(
            ['ticket_id' => $this->selectedTicketId, 'user_id' => Auth::id()],
            [
                'last_read_message_id' => $lastMessage ? $lastMessage->id : null,
                'last_read_at' => now(),
            ]
        );
    }

    public function sendMessage(FileUploadService $uploader)
    {
        if (!$this->selectedTicketId) return;

        $this->validate([
            'newMessage' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $filePath = null;
        if ($this->attachment) {
            $filePath = $uploader->uploadTicketAttachment($this->attachment);
        }

        TicketMessage::create([
            'ticket_id' => $this->selectedTicketId,
            'user_id' => Auth::id(),
            'message' => $this->newMessage,
            'attachment_path' => $filePath,
            'reply_to_message_id' => $this->replyToMessageId,
        ]);

        $ticket = Ticket::find($this->selectedTicketId);
        if ($ticket) {
            $ticket->touch();

            Notification::create([
                'user_id' => $ticket->user_id,
                'title' => 'Support Ticket Reply',
                'message' => "Management replied to your support ticket: {$ticket->subject}",
                'type' => 'info',
                'is_read' => false,
            ]);
        }

        $this->reset(['newMessage', 'attachment', 'replyToMessageId']);
        $this->markAsRead();
    }

    public function updateStatus($status)
    {
        if ($this->selectedTicketId) {
            Ticket::where('id', $this->selectedTicketId)->update(['status' => $status]);

            AuditLog::log(
                Auth::id(),
                "Updated support ticket #{$this->selectedTicketId} status to {$status}",
                'Ticket',
                $this->selectedTicketId
            );

            session()->flash('message', 'Ticket status updated to ' . strtoupper($status));
        }
    }

    public function render()
    {
        $ticketsQuery = Ticket::with(['user', 'stall', 'messages']);

        if ($this->search) {
            $ticketsQuery->where(function ($q) {
                $q->where('issue', 'like', "%{$this->search}%")
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('name', 'like', "%{$this->search}%")
                         ->orWhere('email', 'like', "%{$this->search}%");
                  });
            });
        }

        if ($this->statusFilter) {
            $ticketsQuery->where('status', $this->statusFilter);
        }

        $selectedTicket = null;
        if ($this->selectedTicketId) {
            $selectedTicket = Ticket::with(['user', 'stall', 'messages.user'])->find($this->selectedTicketId);
        }

        return view('livewire.admin.ticket-center', [
            'tickets' => $ticketsQuery->latest()->paginate(10),
            'selectedTicket' => $selectedTicket,
        ]);
    }
}
