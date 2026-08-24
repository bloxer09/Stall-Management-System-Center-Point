<?php

namespace App\Livewire\User;

use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Services\FileUploadService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class TicketManager extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $showCreateModal = false;
    public $showConfirmClose = false;
    public $stall_id = '';
    public $issue = '';

    public $activeTicketId = null;
    public $showThreadModal = false;
    public $isWidgetMinimized = false;

    public $message = '';
    public $attachment = null;
    public $replyToMessageId = null;

    protected $rules = [
        'stall_id' => 'nullable|exists:stalls,id',
        'issue' => 'required|string|min:5',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

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

        $activeLeases = StallMapping::where('user_id', auth()->id())
            ->where('is_active', true)
            ->get();

        if ($activeLeases->isEmpty()) {
            $this->addError('stall_id', 'You must have at least 1 active leased stall to open a support ticket.');
            return;
        }

        $assignedStallId = $this->stall_id ?: $activeLeases->first()->stall_id;

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'stall_id' => $assignedStallId,
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
        $this->openThreadModal($ticket->id);
    }

    public function openThreadModal($id)
    {
        $this->resetValidation();
        $ticket = Ticket::where('user_id', auth()->id())->find($id);

        if (!$ticket) return;

        $this->activeTicketId = $ticket->id;
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
        $this->activeTicketId = null;
        $this->isWidgetMinimized = false;
        $this->reset(['message', 'attachment', 'replyToMessageId']);
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
        $ticketsQuery = Ticket::with(['handler', 'stall', 'messages'])
            ->where('user_id', auth()->id());

        if ($this->search) {
            $ticketsQuery->where('issue', 'like', "%{$this->search}%");
        }

        if ($this->statusFilter) {
            $ticketsQuery->where('status', $this->statusFilter);
        }

        $activeTicket = null;
        $otherParticipant = null;

        if ($this->activeTicketId) {
            $activeTicket = Ticket::with(['user', 'handler', 'stall', 'messages.user', 'participants'])->where('user_id', auth()->id())->find($this->activeTicketId);
            if ($activeTicket) {
                $otherParticipant = TicketParticipant::where('ticket_id', $this->activeTicketId)
                    ->where('user_id', '!=', auth()->id())
                    ->first();
            }
        }

        $myStalls = StallMapping::with('stall')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->get()
            ->pluck('stall');

        return view('livewire.user.ticket-manager', [
            'tickets' => $ticketsQuery->latest()->paginate(10),
            'activeTicket' => $activeTicket,
            'otherParticipant' => $otherParticipant,
            'myStalls' => $myStalls,
        ]);
    }
}
