<?php

namespace App\Livewire\User;

use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Services\FileUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketManager extends Component
{
    use WithFileUploads;

    public $showCreateModal = false;
    public $showConfirmClose = false;
    public $stall_id = '';
    public $issue = '';

    public $activeTicketId = null;
    public $message = '';
    public $attachment = null;
    public $replyToMessageId = null;

    protected $rules = [
        'stall_id' => 'required|exists:stalls,id',
        'issue' => 'required|string|min:5',
    ];

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['stall_id', 'issue']);
        $this->showCreateModal = true;
        $this->showConfirmClose = false;
    }

    public function requestCloseCreateModal()
    {
        if (!empty($this->issue) || !empty($this->stall_id)) {
            $this->showConfirmClose = true;
            return;
        }

        $this->closeCreateModal();
    }

    public function cancelConfirmClose()
    {
        $this->showConfirmClose = false;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->showConfirmClose = false;
        $this->reset(['stall_id', 'issue']);
    }

    public function createTicket()
    {
        $this->validate();

        $myStallIds = StallMapping::where('user_id', auth()->id())
            ->where('is_active', true)
            ->pluck('stall_id')
            ->toArray();

        if (!in_array($this->stall_id, $myStallIds)) {
            $this->addError('stall_id', 'You can only create tickets for your active leased stalls.');
            return;
        }

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'stall_id' => $this->stall_id,
            'issue' => $this->issue,
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'message' => $this->issue,
        ]);

        session()->flash('message', 'Support ticket created successfully!');
        $this->closeCreateModal();
        $this->selectTicket($ticket->id);
    }

    public function selectTicket($id)
    {
        $ticket = Ticket::where('user_id', auth()->id())->find($id);

        if (!$ticket) {
            return;
        }

        $this->activeTicketId = $ticket->id;
        $this->markAsRead();
    }

    public function markAsRead()
    {
        if (!$this->activeTicketId) return;

        $ticket = Ticket::where('user_id', auth()->id())->find($this->activeTicketId);
        if (!$ticket) return;

        $lastMessage = TicketMessage::where('ticket_id', $this->activeTicketId)->latest()->first();

        TicketParticipant::updateOrCreate(
            ['ticket_id' => $this->activeTicketId, 'user_id' => auth()->id()],
            [
                'last_read_message_id' => $lastMessage ? $lastMessage->id : null,
                'last_read_at' => now(),
            ]
        );
    }

    public function sendMessage(FileUploadService $uploader)
    {
        if (!$this->activeTicketId) return;

        $ticket = Ticket::where('user_id', auth()->id())->find($this->activeTicketId);
        if (!$ticket) return;

        $this->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $filePath = null;
        if ($this->attachment) {
            $filePath = $uploader->uploadTicketAttachment($this->attachment);
        }

        TicketMessage::create([
            'ticket_id' => $this->activeTicketId,
            'user_id' => auth()->id(),
            'message' => $this->message,
            'attachment_path' => $filePath,
            'reply_to_message_id' => $this->replyToMessageId,
        ]);

        $this->reset(['message', 'attachment', 'replyToMessageId']);
        $this->markAsRead();
    }

    public function render()
    {
        $tickets = Ticket::with(['stall'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $activeTicket = null;
        $messages = [];

        if ($this->activeTicketId) {
            $activeTicket = Ticket::with(['stall'])->where('user_id', auth()->id())->find($this->activeTicketId);
            if ($activeTicket) {
                $messages = TicketMessage::with(['user', 'parentMessage.user'])
                    ->where('ticket_id', $this->activeTicketId)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        $myStalls = StallMapping::with('stall')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->get()
            ->pluck('stall');

        return view('livewire.user.ticket-manager', [
            'tickets' => $tickets,
            'activeTicket' => $activeTicket,
            'messages' => $messages,
            'myStalls' => $myStalls,
        ]);
    }
}
