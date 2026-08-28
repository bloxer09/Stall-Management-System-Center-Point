<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Helpdesk & Support</h1>
            <p class="text-xs text-base-content/60 mt-1">Send questions and message management for support.</p>
        </div>
        <button wire:click="openCreateModal"
            class="btn btn-primary btn-sm rounded-xl font-bold shadow-xs flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Ticket
        </button>
    </div>

    <!-- Support Tickets Table Container -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <!-- Search & Filter Bar -->
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search ticket description..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All</option>
                    <option value="open">Open</option>
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
                        <th class="font-bold">Assigned Admin</th>
                        <th class="font-bold">Description</th>
                        <th class="font-bold">Status</th>
                        <th class="font-bold">Updated</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono text-sm font-bold text-primary">
                                #TKT-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                @if($t->handler)
                                    <div class="flex items-center gap-2">
                                        <div class="avatar">
                                            <div
                                                class="w-7 h-7 rounded-full overflow-hidden border border-success/30 shadow-xs">
                                                <img src="{{ $t->handler->avatar_url }}" onclick="openImageLightbox(this.src)"
                                                    class="w-full h-full object-cover cursor-pointer" alt="Handler Avatar"
                                                    title="Click to view full photo">
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-base-content">{{ $t->handler->name }}</span>
                                    </div>
                                @else
                                    <span class="badge badge-warning badge-sm font-bold text-[10px]">Unassigned</span>
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
                                @php $newChats = $t->unread_count; @endphp
                                <button wire:click="openThreadModal({{ $t->id }})"
                                    class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs whitespace-nowrap">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    Chat{{ $newChats > 0 ? " ({$newChats})" : '' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-base-content/40 text-xs font-semibold">
                                No support tickets found. Click "New Support Ticket" above to open an inquiry.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $tickets->links() }}
        </div>
    </div>

    <!-- Messenger-Style Floating Mini Chat Tab Widget (Bottom-Right Docked) -->
    @if($showThreadModal && $activeTicket)
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
                    {{ $activeTicket->handler->name ?? 'Support Chat' }}
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
                        <div class="avatar placeholder">
                            <div
                                class="bg-primary text-primary-content rounded-full w-8 h-8 font-bold text-xs flex items-center justify-center">
                                <span>{{ strtoupper(substr($activeTicket->handler->name ?? 'S', 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="truncate">
                            <div class="font-bold text-xs text-base-content truncate flex items-center gap-1.5">
                                <span>Helpdesk Support</span>
                                <span
                                    class="badge badge-xs font-mono font-bold">{{ '#TKT-' . str_pad($activeTicket->id, 5, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="text-[10px] text-base-content/60 truncate flex items-center gap-1">
                                <span>Assigned Admin:</span>
                                @if($activeTicket->handler)
                                    <span class="font-bold text-base-content">{{ $activeTicket->handler->name }}</span>
                                @else
                                    <span class="text-warning font-bold">Unassigned</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
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
                    @forelse($activeTicket->messages as $msg)
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
                                @if(!empty($msg->attachments_list))
                                    <div class="mt-2 pt-2 border-t border-current/20 flex flex-wrap gap-2">
                                        @foreach($msg->attachments_list as $attIndex => $attPath)
                                            @php
                                                $ext = strtolower(pathinfo($attPath, PATHINFO_EXTENSION));
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                $attUrl = route('file.ticket-attachment', ['id' => $msg->id, 'index' => $attIndex]);
                                            @endphp
                                            @if($isImage)
                                                <div onclick="openImageLightbox('{{ $attUrl }}')"
                                                    class="block group cursor-pointer">
                                                    <img src="{{ $attUrl }}" alt="Attachment Preview"
                                                        class="max-w-[180px] max-h-36 rounded-xl object-cover border border-base-300 shadow-xs group-hover:scale-105 transition cursor-pointer"
                                                        title="Click to view full photo">
                                                </div>
                                            @else
                                                <a href="{{ $attUrl }}" target="_blank"
                                                    class="flex items-center gap-1.5 p-2 rounded-xl bg-base-100/30 border border-current/20 text-xs font-bold underline">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                    </svg>
                                                    Attachment ({{ strtoupper($ext) }})
                                                </a>
                                            @endif
                                        @endforeach
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

                <!-- Chat Reply Footer Form -->
                @if($activeTicket->status !== 'closed')
                    <form wire:submit.prevent="sendMessage"
                        x-data="{
                            handleChatPaste(e) {
                                const clipboard = e.clipboardData || window.clipboardData;
                                if (!clipboard) return;

                                const filesToUpload = [];

                                if (clipboard.files && clipboard.files.length > 0) {
                                    for (let i = 0; i < clipboard.files.length; i++) {
                                        filesToUpload.push(clipboard.files[i]);
                                    }
                                }

                                if (filesToUpload.length === 0 && clipboard.items) {
                                    for (let i = 0; i < clipboard.items.length; i++) {
                                        if (clipboard.items[i].kind === 'file') {
                                            const file = clipboard.items[i].getAsFile();
                                            if (file) {
                                                filesToUpload.push(file);
                                            }
                                        }
                                    }
                                }

                                if (filesToUpload.length > 0) {
                                    e.preventDefault();
                                    @this.uploadMultiple('incomingAttachments', filesToUpload);
                                }
                            }
                        }"
                        x-on:paste="handleChatPaste($event)"
                        class="p-3 bg-base-200/50 border-t border-base-300 flex flex-col gap-2">

                        <!-- Uploading Indicator -->
                        <div wire:loading wire:target="incomingAttachments" class="flex items-center gap-2 text-[11px] text-primary bg-primary/10 px-3 py-1.5 rounded-xl border border-primary/20 animate-pulse">
                            <span class="loading loading-spinner loading-xs"></span>
                            <span class="font-bold">Attaching files...</span>
                        </div>

                        <!-- Rich Previews of Attached / Pasted Files -->
                        @if(!empty($attachments))
                            <div class="flex flex-wrap gap-2 p-2 bg-base-100 rounded-2xl border border-base-300 max-h-36 overflow-y-auto">
                                @foreach($attachments as $index => $file)
                                    @php
                                        $isImg = str_starts_with($file->getMimeType() ?? '', 'image/') || in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    @endphp
                                    <div class="relative group flex items-center gap-2 p-1.5 bg-base-200/60 rounded-xl border border-base-300 shadow-2xs pr-6">
                                        @if($isImg)
                                            <img src="{{ $file->temporaryUrl() }}" 
                                                onclick="openImageLightbox(this.src)" 
                                                class="w-10 h-10 rounded-lg object-cover cursor-pointer hover:opacity-90 transition border border-base-300" 
                                                title="Click to preview full photo">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-base-300 flex flex-col items-center justify-center text-base-content/70">
                                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span class="text-[7px] font-mono font-bold uppercase">{{ $file->getClientOriginalExtension() }}</span>
                                            </div>
                                        @endif
                                        <div class="max-w-[100px] min-w-0">
                                            <p class="text-[10px] font-bold truncate text-base-content" title="{{ $file->getClientOriginalName() }}">
                                                {{ $file->getClientOriginalName() }}
                                            </p>
                                            <p class="text-[9px] text-base-content/50">
                                                {{ number_format($file->getSize() / 1024, 0) }} KB
                                            </p>
                                        </div>
                                        <button type="button" 
                                            wire:click="removeAttachment({{ $index }})" 
                                            class="absolute top-1 right-1 btn btn-circle btn-ghost btn-2xs text-error hover:bg-error/20" 
                                            title="Remove attachment">
                                            &times;
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            <label class="btn btn-ghost btn-circle btn-xs text-base-content/70 hover:text-primary cursor-pointer"
                                title="Attach File(s) or Paste (Ctrl+V)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <input type="file" wire:model="incomingAttachments" multiple class="hidden">
                            </label>
                            <input type="text" wire:model="message" placeholder="Type a message or paste files..."
                                class="input input-sm input-bordered flex-1 rounded-xl text-xs" />
                            <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold px-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-3 bg-base-200 text-center text-xs font-bold text-base-content/50 border-t border-base-300">
                        This ticket is closed.
                    </div>
                @endif
            </div>
        @endif
    @endif

    <!-- New Support Ticket Modal -->
    @if($showCreateModal)
        <dialog class="modal modal-open">
            <div
                class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="requestCloseCreateModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                @if($showConfirmClose)
                    <div class="p-4 bg-error/10 border border-error/30 rounded-2xl text-center space-y-2">
                        <h4 class="font-extrabold text-sm text-base-content">Discard Ticket Draft?</h4>
                        <p class="text-xs text-base-content/70">Your changes will be lost.</p>
                        <div class="flex gap-2 justify-center pt-2">
                            <button wire:click="cancelConfirmClose" class="btn btn-xs btn-outline rounded-lg">Keep
                                Editing</button>
                            <button wire:click="closeCreateModal" class="btn btn-xs btn-error text-white rounded-lg">Yes,
                                Discard</button>
                        </div>
                    </div>
                @endif

                <h3 class="font-black text-lg text-base-content">Create Support Ticket</h3>
                <form wire:submit.prevent="createTicket" class="space-y-4">
                    @if(count($myStalls) > 0)
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-xs uppercase">Select Leased Stall
                                    (Optional)</span></label>
                            <select wire:model="stall_id" class="select select-bordered w-full rounded-xl text-sm">
                                <option value="">-- General Inquiry / No Stall --</option>
                                @foreach($myStalls as $st)
                                    <option value="{{ $st->id }}">Stall #{{ $st->stall_number }}
                                        ({{ $st->location->name ?? 'Main' }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Description</span></label>
                        <textarea wire:model="issue" rows="4" placeholder="Describe your inquiry or maintenance issue..."
                            required class="textarea textarea-bordered w-full rounded-xl text-sm"></textarea>
                        @error('issue') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                        @error('stall_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="requestCloseCreateModal"
                            class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold">Submit Ticket</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="requestCloseCreateModal">close</button></form>
        </dialog>
    @endif
</div>