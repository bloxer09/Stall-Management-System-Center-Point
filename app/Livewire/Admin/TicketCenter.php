<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Models\User;
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
    public $showThreadModal = false;
    public $isWidgetMinimized = false;

    // Chat Message Inputs
    public $newMessage = '';
    public $attachment = null;
    public $replyToMessageId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

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

    public function claimTicket($id = null)
    {
        $targetId = $id ?: $this->selectedTicketId;
        if (!$targetId) return;

        $ticket = Ticket::find($targetId);
        if ($ticket) {
            $ticket->update(['assigned_to' => Auth::id()]);

            Notification::send(
                $ticket->user_id,
                'ticket_update',
                "Ticket #TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . " Assigned",
                Auth::user()->name . " has claimed and is now handling your support ticket.",
                route('user.tickets')
            );

            AuditLog::log(
                Auth::id(),
                "Claimed support ticket #{$ticket->id} handler assignment",
                'Ticket',
                $ticket->id
            );

            $this->dispatch('notify', type: 'success', message: "Support ticket #{$ticket->id} assigned to you.");
        }
    }

    public function reassignTicket($ticketId, $newAdminId)
    {
        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $ticket->update(['assigned_to' => $newAdminId]);

            $newAdmin = User::find($newAdminId);
            AuditLog::log(
                Auth::id(),
                "Reassigned support ticket #{$ticket->id} to " . ($newAdmin->name ?? 'Admin'),
                'Ticket',
                $ticket->id
            );

            $this->dispatch('notify', type: 'success', message: "Ticket reassigned to " . ($newAdmin->name ?? 'Admin'));
        }
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

        $ticket = Ticket::find($this->selectedTicketId);
        if (!$ticket) return;

        // Handler Lock Enforcement: Only assigned admin (or auto-claim if unassigned) can reply
        if ($ticket->assigned_to === null) {
            $ticket->update(['assigned_to' => Auth::id()]);
        } elseif ($ticket->assigned_to !== Auth::id()) {
            $handlerName = $ticket->handler->name ?? 'another staff member';
            $this->addError('newMessage', "This support ticket is currently handled by {$handlerName}.");
            return;
        }

        $this->validate([
            'newMessage' => $this->attachment ? 'nullable|string' : 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $filePath = null;
        if ($this->attachment) {
            $filePath = $uploader->uploadTicketAttachment($this->attachment);
        }

        $messageContent = trim($this->newMessage) !== '' ? $this->newMessage : 'Sent an attachment';

        TicketMessage::create([
            'ticket_id' => $this->selectedTicketId,
            'user_id' => Auth::id(),
            'message' => $messageContent,
            'attachment_path' => $filePath,
            'reply_to_message_id' => $this->replyToMessageId,
        ]);

        $ticket->touch();

        Notification::send(
            $ticket->user_id,
            'ticket_message',
            "Support Ticket Reply (#TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . ")",
            Auth::user()->name . ": " . \Illuminate\Support\Str::limit($this->newMessage, 60),
            route('user.tickets')
        );

        $this->reset(['newMessage', 'attachment', 'replyToMessageId']);
        $this->markAsRead();
    }

    public function updateStatus($status)
    {
        if ($this->selectedTicketId) {
            $ticket = Ticket::find($this->selectedTicketId);
            if ($ticket) {
                if ($ticket->assigned_to === null) {
                    $ticket->update(['assigned_to' => Auth::id(), 'status' => $status]);
                } else {
                    $ticket->update(['status' => $status]);
                }

                Notification::send(
                    $ticket->user_id,
                    'ticket_update',
                    "Ticket #TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT) . " Status Updated",
                    "Your support ticket status has been updated to " . strtoupper($status) . ".",
                    route('user.tickets')
                );

                AuditLog::log(
                    Auth::id(),
                    "Updated support ticket #{$this->selectedTicketId} status to {$status}",
                    'Ticket',
                    $this->selectedTicketId
                );

                $this->dispatch('notify', type: 'success', message: 'Ticket status updated to ' . strtoupper($status));
            }
        }
    }

    public function render()
    {
        $ticketsQuery = Ticket::with(['user', 'handler', 'stall', 'messages', 'participants']);

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
        $otherParticipant = null;

        if ($this->selectedTicketId) {
            $selectedTicket = Ticket::with(['user', 'handler', 'stall', 'messages.user', 'participants'])->find($this->selectedTicketId);
            if ($selectedTicket) {
                $otherParticipant = TicketParticipant::where('ticket_id', $this->selectedTicketId)
                    ->where('user_id', '!=', Auth::id())
                    ->first();
            }
        }

        $staffUsers = User::whereIn('role', ['admin', 'staff'])->get();

        return view('livewire.admin.ticket-center', [
            'tickets' => $ticketsQuery->latest()->paginate(10),
            'selectedTicket' => $selectedTicket,
            'otherParticipant' => $otherParticipant,
            'staffUsers' => $staffUsers,
        ]);
    }
}
