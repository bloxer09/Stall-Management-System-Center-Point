<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-black tracking-tight">Support Tickets & Threaded Chat</h1>
            <p class="text-xs text-base-content/60 mt-1">Communicate directly with stall management administrative staff</p>
        </div>
        <button wire:click="openCreateModal" class="btn btn-primary rounded-xl font-bold shadow">
            + New Support Ticket
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Ticket List Sidebar -->
        <div class="card bg-base-100 shadow-md border border-base-300 rounded-3xl p-4 h-[600px] flex flex-col">
            <h2 class="font-extrabold text-sm px-2 pb-3 border-b border-base-200">My Support Tickets</h2>
            <div class="overflow-y-auto flex-1 space-y-2 mt-3">
                @forelse($tickets as $t)
                    <div wire:click="selectTicket({{ $t->id }})" class="p-3.5 rounded-2xl cursor-pointer transition border border-base-200 {{ $activeTicketId === $t->id ? 'bg-primary/10 border-primary font-semibold' : 'bg-base-100 hover:bg-base-200' }}">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-base-content/60">#{{ $t->id }}</span>
                            <span class="badge badge-sm font-bold {{ $t->status === 'open' ? 'badge-info text-white' : ($t->status === 'closed' ? 'badge-neutral' : 'badge-warning') }}">
                                {{ $t->status }}
                            </span>
                        </div>
                        <div class="font-bold text-sm text-base-content mt-1 text-ellipsis overflow-hidden whitespace-nowrap">{{ $t->issue }}</div>
                        <div class="text-[11px] text-base-content/50 mt-1">{{ $t->updated_at->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="text-center py-12 text-xs text-base-content/50">No support tickets created yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Threaded Chat Message Board -->
        <div @if($activeTicket) wire:poll.3s="markAsRead" @endif class="lg:col-span-2 card bg-base-100 shadow-md border border-base-300 rounded-3xl p-6 h-[600px] flex flex-col">
            @if($activeTicket)
                <!-- Chat Header -->
                <div class="flex items-center justify-between pb-4 border-b border-base-200">
                    <div>
                        <span class="badge badge-primary font-bold">Ticket #{{ $activeTicket->id }}</span>
                        <h2 class="text-lg font-bold text-base-content mt-1">{{ $activeTicket->issue }}</h2>
                    </div>
                    <span class="badge badge-lg font-bold uppercase {{ $activeTicket->status === 'open' ? 'badge-info text-white' : 'badge-neutral' }}">
                        {{ $activeTicket->status }}
                    </span>
                </div>

                <!-- Messages Stream -->
                <div class="flex-1 overflow-y-auto py-4 space-y-4 px-2">
                    @forelse($messages as $msg)
                        <div class="chat {{ $msg->user_id === auth()->id() ? 'chat-end' : 'chat-start' }}">
                            <div class="chat-header text-xs text-base-content/60 font-semibold mb-1">
                                {{ $msg->user->name }}
                                <time class="text-[10px] opacity-60 ms-1">{{ $msg->created_at->format('h:i A') }}</time>
                            </div>
                            <div class="chat-bubble font-medium text-sm {{ $msg->user_id === auth()->id() ? 'chat-bubble-primary text-white' : 'bg-base-200 text-base-content' }}">
                                {{ $msg->message }}
                                @if($msg->attachment_path)
                                    <div class="mt-2 pt-2 border-t border-white/20">
                                        <a href="{{ route('file.ticket-attachment', $msg->id) }}" target="_blank" class="text-xs underline font-bold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            View Attachment
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <div class="chat-footer opacity-50 text-[10px] mt-1">{{ $msg->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-xs text-base-content/50">No messages in this thread yet.</div>
                    @endforelse
                </div>

                <!-- Chat Reply Input -->
                @if($activeTicket->status !== 'closed')
                    <form wire:submit="sendMessage" class="pt-4 border-t border-base-200 flex flex-col gap-2">
                        @if($attachment)
                            <div class="flex items-center justify-between text-xs bg-primary/10 text-primary px-3 py-1.5 rounded-xl border border-primary/20">
                                <span class="truncate font-bold max-w-[280px]">📎 {{ $attachment->getClientOriginalName() }}</span>
                                <button type="button" wire:click="$set('attachment', null)" class="text-error font-bold hover:scale-110 ml-2">&times;</button>
                            </div>
                        @endif
                        <div class="flex gap-2 items-center">
                            <label class="btn btn-ghost btn-circle btn-sm text-base-content/70 hover:text-primary cursor-pointer" title="Attach File or Image">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <input type="file" wire:model="attachment" class="hidden">
                            </label>
                            <input type="text" wire:model="message" placeholder="Type your reply message..." required class="input input-bordered flex-1 rounded-xl text-sm" />
                            <button type="submit" class="btn btn-primary rounded-xl font-bold px-6">
                                Send Reply
                            </button>
                        </div>
                    </form>
                @else
                    <div class="pt-4 border-t border-base-200 text-center text-xs font-bold text-base-content/50">
                        This support ticket is closed.
                    </div>
                @endif
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-base-content/40 space-y-2">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p class="text-sm font-semibold">Select a support ticket from the sidebar to start chat thread</p>
                </div>
            @endif
        </div>
    </div>

    <!-- New Ticket Modal -->
    @if($showCreateModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative">
            <button wire:click="requestCloseCreateModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            @if($showConfirmClose)
            <div class="p-4 bg-error/10 border border-error/30 rounded-2xl mb-4 text-center space-y-2">
                <h4 class="font-extrabold text-sm text-base-content">Discard Draft Support Ticket?</h4>
                <p class="text-xs text-base-content/70">Your typed ticket details will be lost.</p>
                <div class="flex gap-2 justify-center pt-2">
                    <button wire:click="cancelConfirmClose" class="btn btn-xs btn-outline rounded-lg">Continue Editing</button>
                    <button wire:click="closeCreateModal" class="btn btn-xs btn-error text-white rounded-lg">Yes, Discard</button>
                </div>
            </div>
            @endif

            <h3 class="font-extrabold text-lg mb-4">Create New Support Ticket</h3>
            <form wire:submit="createTicket" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Select Assigned Stall</span></label>
                    <select wire:model="stall_id" required class="select select-bordered w-full rounded-xl">
                        <option value="">-- Choose Your Leased Stall --</option>
                        @foreach($myStalls as $st)
                            <option value="{{ $st->id }}">Stall #{{ $st->stall_number }} ({{ $st->location->name ?? 'Main' }})</option>
                        @endforeach
                    </select>
                    @error('stall_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold">Describe Issue / Inquiry</span></label>
                    <textarea wire:model="issue" rows="4" placeholder="Describe your issue or inquiry in detail..." required class="textarea textarea-bordered w-full rounded-xl"></textarea>
                    @error('issue') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold">Submit Ticket</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="requestCloseCreateModal">close</button></form>
    </dialog>
    @endif
</div>
