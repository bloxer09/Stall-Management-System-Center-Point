<?php

namespace App\Livewire\User;

use App\Models\StallMapping;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketParticipant;
use App\Services\FileUploadService;
use Illuminate\Validation\Rule;
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
    public $attachments = [];
    public $incomingAttachments = [];
    public $replyToMessageId = null;

    public function updatedIncomingAttachments()
    {
        foreach ($this->incomingAttachments as $file) {
            $this->attachments[] = $file;
        }
        $this->incomingAttachments = [];
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    protected function rules(): array
    {
        $ownActiveStallIds = StallMapping::where('user_id', auth()->id())
            ->where('is_active', true)
            ->pluck('stall_id')
            ->all();

        return [
            'stall_id' => ['nullable', Rule::in($ownActiveStallIds)],
            'issue' => 'required|string|min:5',
        ];
    }

    protected function messages(): array
    {
        return [
            'stall_id.in' => 'You can only report issues for stalls you currently lease.',
        ];
    }

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

        $assignedStallId = $this->stall_id ?: ($activeLeases->first()?->stall_id ?? null);

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

        $admins = \App\Models\User::whereIn('role', ['admin', 'staff'])->get();
        foreach ($admins as $adm) {
            \App\Models\Notification::send(
                $adm->id,
                'ticket',
                "New Ticket #TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT),
                auth()->user()->name . " submitted a new ticket: " . \Illuminate\Support\Str::limit($this->issue, 60),
                route('admin.tickets')
            );
        }

        $this->dispatch('notify', type: 'success', message: 'Support ticket created successfully!');
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
        $this->reset(['message', 'attachments', 'incomingAttachments', 'replyToMessageId']);
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
            'message' => !empty($this->attachments) ? 'nullable|string' : 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $savedPaths = [];
        if (!empty($this->attachments)) {
            $savedPaths = $uploader->uploadTicketAttachments($this->attachments);
        }

        $attachmentPathValue = null;
        if (count($savedPaths) === 1) {
            $attachmentPathValue = $savedPaths[0];
        } elseif (count($savedPaths) > 1) {
            $attachmentPathValue = json_encode($savedPaths);
        }

        $messageContent = trim($this->message) !== ''
            ? $this->message
            : (count($savedPaths) > 1 ? 'Sent ' . count($savedPaths) . ' attachments' : 'Sent an attachment');

        TicketMessage::create([
            'ticket_id' => $this->activeTicketId,
            'user_id' => auth()->id(),
            'message' => $messageContent,
            'attachment_path' => $attachmentPathValue,
            'reply_to_message_id' => $this->replyToMessageId,
        ]);

        $ticket->touch();

        if ($ticket->assigned_to) {
            \App\Models\Notification::send(
                $ticket->assigned_to,
                'ticket_message',
                "New reply on Ticket #TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT),
                auth()->user()->name . ": " . \Illuminate\Support\Str::limit($messageContent, 60),
                route('admin.tickets')
            );
        } else {
            $admins = \App\Models\User::whereIn('role', ['admin', 'staff'])->get();
            foreach ($admins as $adm) {
                \App\Models\Notification::send(
                    $adm->id,
                    'ticket_message',
                    "New message on Ticket #TKT-" . str_pad($ticket->id, 5, '0', STR_PAD_LEFT),
                    auth()->user()->name . ": " . \Illuminate\Support\Str::limit($messageContent, 60),
                    route('admin.tickets')
                );
            }
        }

        $this->reset(['message', 'attachments', 'incomingAttachments', 'replyToMessageId']);
        $this->markAsRead();
    }

    public function render()
    {
        $ticketsQuery = Ticket::with(['handler', 'stall', 'messages', 'participants'])
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
