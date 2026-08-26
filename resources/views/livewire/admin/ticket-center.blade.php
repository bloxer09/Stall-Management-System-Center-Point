<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Support Ticket Center</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage tenant support requests, ticket handler assignments, and
                live messenger chat threads</p>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="card bg-base-100 shadow-md border border-base-300 rounded-3xl overflow-hidden">
        <!-- Search & Filter Bar -->
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search issue or tenant name..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex gap-2">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Ticket ID</th>
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Assigned Admin</th>
                        <th class="font-bold">Description</th>
                        <th class="font-bold">Status</th>
                        <th class="font-bold">Updated</th>
                        <th class="text-right font-bold whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono text-sm font-bold text-primary">
                                #TKT-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <div class="avatar">
                                        <div
                                            class="w-8 h-8 rounded-full overflow-hidden border border-primary/20 shadow-xs">
                                            <img src="{{ $t->user->avatar_url }}" onclick="openImageLightbox(this.src)"
                                                class="w-full h-full object-cover cursor-pointer" alt="Tenant Avatar"
                                                title="Click to view full photo">
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-sm text-base-content">
                                            {{ $t->user->name ?? 'Tenant' }}
                                        </div>
                                        <div class="text-xs font-medium text-base-content/60">{{ $t->user->email ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($t->handler)
                                    <div class="flex items-center gap-2">
                                        <div class="avatar">
                                            <div
                                                class="w-6 h-6 rounded-full overflow-hidden border border-success/30 shadow-xs">
                                                <img src="{{ $t->handler->avatar_url }}" onclick="openImageLightbox(this.src)"
                                                    class="w-full h-full object-cover cursor-pointer" alt="Handler Avatar"
                                                    title="Click to view full photo">
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-base-content">{{ $t->handler->name }}</span>
                                    </div>
                                @else
                                    <span class="badge badge-warning badge-sm font-bold opacity-80">Unassigned</span>
                                @endif
                            </td>
                            <td class="font-semibold text-sm text-base-content max-w-xs truncate">
                                {{ $t->issue }}
                            </td>
                            <td>
                                <span
                                    class="badge badge-md font-bold uppercase {{ $t->status === 'open' ? 'badge-info text-white' : ($t->status === 'closed' ? 'badge-neutral' : 'badge-warning') }}">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="text-xs font-semibold text-base-content/70">
                                {{ $t->updated_at->diffForHumans() }}
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                                    @if($t->assigned_to === null)
                                        <button wire:click="claimTicket({{ $t->id }})"
                                            class="btn btn-warning btn-sm rounded-xl font-bold shadow-xs whitespace-nowrap">
                                            + Claim
                                        </button>
                                    @endif
                                    @php $newChats = $t->unread_count; @endphp
                                    <button wire:click="openThreadModal({{ $t->id }})"
                                        class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        Chat{{ $newChats > 0 ? " ({$newChats})" : '' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No tickets found</p>
                                        <p class="text-xs mt-0.5">Tenant support requests will appear here when submitted.
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-base-300 bg-base-100">
            {{ $tickets->links() }}
        </div>
    </div>

    <!-- Messenger-Style Floating Mini Chat Tab Widget (Bottom-Right Docked) -->
    @if($showThreadModal && $selectedTicket)
        @if($isWidgetMinimized)
            <!-- Minimized Floating Tab Pill -->
            <div wire:click="toggleWidgetMinimize"
                class="fixed bottom-4 right-4 z-50 flex items-center gap-3 bg-primary text-primary-content px-4 py-3 rounded-full shadow-2xl cursor-pointer hover:scale-105 transition transform">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-success rounded-full ring-2 ring-primary"></span>
                </div>
                <div class="text-xs font-bold truncate max-w-[140px]">
                    {{ $selectedTicket->user->name ?? 'Tenant' }}
                </div>
                <button wire:click.stop="closeThreadModal"
                    class="btn btn-ghost btn-circle btn-xs text-primary-content hover:bg-white/20">
                    &times;
                </button>
            </div>
        @else
            <!-- Expanded Messenger Chat Box -->
            <div wire:poll.3s="markAsRead"
                class="fixed bottom-4 right-4 z-50 w-80 md:w-96 h-[520px] bg-base-100 border border-base-300 rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in slide-in-from-bottom duration-200">
                <!-- Mini Tab Header -->
                <div
                    class="bg-base-200/90 backdrop-blur-md px-4 py-3 border-b border-base-300 flex items-center justify-between">
                    <div class="flex items-center gap-2.5 truncate">
                        <div class="avatar">
                            <div class="w-8 h-8 rounded-full overflow-hidden border border-primary/20 shadow-xs">
                                <img src="{{ $selectedTicket->user->avatar_url }}" onclick="openImageLightbox(this.src)"
                                    class="w-full h-full object-cover cursor-pointer" alt="Tenant Avatar"
                                    title="Click to view full photo">
                            </div>
                        </div>
                        <div class="truncate">
                            <div class="font-bold text-xs text-base-content truncate flex items-center gap-1.5">
                                <span>{{ $selectedTicket->user->name ?? 'Tenant' }}</span>
                                <span
                                    class="badge badge-xs font-mono font-bold">{{ '#TKT-' . str_pad($selectedTicket->id, 5, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="text-[10px] text-base-content/60 truncate flex items-center gap-1">
                                <span>Assigned Admin: {{ $selectedTicket->handler->name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        @if($selectedTicket->status === 'open')
                            <button wire:click="updateStatus('closed')"
                                class="btn btn-ghost btn-xs text-[10px] font-bold text-neutral" title="Close Ticket">Close</button>
                        @else
                            <button wire:click="updateStatus('open')"
                                class="btn btn-ghost btn-xs text-[10px] font-bold text-success"
                                title="Reopen Ticket">Reopen</button>
                        @endif
                        <button wire:click="toggleWidgetMinimize" class="btn btn-ghost btn-square btn-xs text-base-content/70"
                            title="Minimize">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </button>
                        <button wire:click="closeThreadModal"
                            class="btn btn-ghost btn-square btn-xs text-base-content/70 hover:text-error" title="Close">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Chat Message Stream -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-base-100">
                    @forelse($selectedTicket->messages as $msg)
                        <div class="chat {{ $msg->user_id === auth()->id() ? 'chat-end' : 'chat-start' }}">
                            <div class="chat-image avatar">
                                <div class="w-7 h-7 rounded-full overflow-hidden border border-base-300 shadow-xs">
                                    <img src="{{ $msg->user->avatar_url }}" onclick="openImageLightbox(this.src)"
                                        class="w-full h-full object-cover cursor-pointer" alt="User Avatar"
                                        title="Click to view photo">
                                </div>
                            </div>
                            <div class="chat-header text-[10px] text-base-content/50 font-semibold mb-0.5 flex items-center gap-1">
                                <span>{{ $msg->user->name }}</span>
                                <time class="text-[9px] opacity-60 ml-1">{{ $msg->created_at->format('h:i A') }}</time>
                            </div>
                            <div
                                class="chat-bubble text-xs font-medium {{ $msg->user_id === auth()->id() ? 'chat-bubble-primary text-primary-content' : 'bg-base-200 text-base-content' }}">
                                <div>{{ $msg->message }}</div>
                                @if($msg->attachment_path)
                                    @php
                                        $ext = strtolower(pathinfo($msg->attachment_path, PATHINFO_EXTENSION));
                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="mt-2 pt-2 border-t border-current/20">
                                        @if($isImage)
                                            <div onclick="openImageLightbox('{{ route('file.ticket-attachment', $msg->id) }}')"
                                                class="block group cursor-pointer">
                                                <img src="{{ route('file.ticket-attachment', $msg->id) }}" alt="Attachment Preview"
                                                    class="max-w-[200px] max-h-40 rounded-xl object-cover border border-base-300 shadow-xs group-hover:scale-105 transition cursor-pointer"
                                                    title="Click to view full photo">
                                            </div>
                                        @else
                                            <a href="{{ route('file.ticket-attachment', $msg->id) }}" target="_blank"
                                                class="flex items-center gap-1.5 p-2 rounded-xl bg-base-100/30 border border-current/20 text-xs font-bold underline">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                </svg>
                                                Attachment ({{ strtoupper($ext) }})
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if($msg->user_id === auth()->id())
                                <div
                                    class="text-[9px] font-bold text-right mt-0.5 opacity-80 {{ $otherParticipant && $otherParticipant->last_read_message_id >= $msg->id ? 'text-primary' : 'text-base-content/50' }}">
                                    {{ $otherParticipant && $otherParticipant->last_read_message_id >= $msg->id ? 'Seen' : 'Delivered' }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-10 text-xs text-base-content/40">No messages in this chat thread yet.</div>
                    @endforelse
                </div>

                <!-- Chat Reply Footer Form / Single-Admin Lock Banner -->
                @if($selectedTicket->status !== 'closed')
                    @if($selectedTicket->assigned_to === null)
                        <div class="p-4 bg-warning/10 border-t border-warning/30 text-center space-y-2">
                            <div class="text-xs font-extrabold text-base-content">This support ticket is unassigned.</div>
                            <p class="text-[11px] text-base-content/70">You must claim this ticket to send responses to the tenant.</p>
                            <button wire:click="claimTicket({{ $selectedTicket->id }})"
                                class="btn btn-xs btn-primary rounded-xl font-bold shadow-xs">
                                + Claim Ticket Now
                            </button>
                        </div>
                    @elseif($selectedTicket->assigned_to !== auth()->id())
                        <div class="p-4 bg-base-200 border-t border-base-300 text-center space-y-2">
                            <div class="text-xs font-extrabold text-base-content">Handled by
                                {{ $selectedTicket->handler->name ?? 'Another Admin' }}
                            </div>
                            <p class="text-[11px] text-base-content/60">Only the assigned admin can send responses.</p>
                            <button wire:click="claimTicket({{ $selectedTicket->id }})"
                                class="btn btn-xs btn-outline rounded-xl font-bold">
                                Reassign / Transfer Ticket to Me
                            </button>
                        </div>
                    @else
                        <form wire:submit.prevent="sendMessage"
                            x-data="{
                                handleChatPaste(e) {
                                    const clipboard = e.clipboardData || window.clipboardData;
                                    if (!clipboard) return;
                                    const files = clipboard.files;
                                    if (files && files.length > 0) {
                                        e.preventDefault();
                                        @this.upload('attachment', files[0]);
                                        return;
                                    }
                                    const items = clipboard.items;
                                    if (items) {
                                        for (let i = 0; i < items.length; i++) {
                                            if (items[i].kind === 'file') {
                                                const file = items[i].getAsFile();
                                                if (file) {
                                                    e.preventDefault();
                                                    @this.upload('attachment', file);
                                                    return;
                                                }
                                            }
                                        }
                                    }
                                }
                            }"
                            x-on:paste="handleChatPaste($event)"
                            class="p-3 bg-base-200/50 border-t border-base-300 flex flex-col gap-2">

                            <!-- Uploading Indicator -->
                            <div wire:loading wire:target="attachment" class="flex items-center gap-2 text-[11px] text-primary bg-primary/10 px-3 py-1 rounded-xl border border-primary/20 animate-pulse">
                                <span class="loading loading-spinner loading-xs"></span>
                                <span class="font-bold">Attaching file...</span>
                            </div>

                            @if($attachment)
                                <div
                                    class="flex items-center justify-between text-[11px] bg-primary/10 text-primary px-3 py-1 rounded-xl border border-primary/20">
                                    <span class="truncate font-bold max-w-[220px]">📎 {{ $attachment->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="$set('attachment', null)"
                                        class="text-error font-bold hover:scale-110 ml-2">&times;</button>
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <label class="btn btn-ghost btn-circle btn-xs text-base-content/70 hover:text-primary cursor-pointer"
                                    title="Attach File or Paste (Ctrl+V)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <input type="file" wire:model="attachment" class="hidden">
                                </label>
                                <input type="text" wire:model="newMessage" placeholder="Type response or paste file (Ctrl+V)..."
                                    class="input input-sm input-bordered flex-1 rounded-xl text-xs" />
                                <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold px-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    @endif
                @else
                    <div class="p-3 bg-base-200 text-center text-xs font-bold text-base-content/50 border-t border-base-300">
                        This support ticket is closed.
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>