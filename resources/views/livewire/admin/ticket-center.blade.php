<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight">Support Ticket Center</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage tenant support requests, maintenance inquiries, and live chat threads</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Lodgify Data Table Container -->
    <div class="card bg-base-100 shadow-md border border-base-300 rounded-3xl overflow-hidden">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search issue or tenant name..." class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex gap-2">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Statuses</option>
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
                        <th class="font-bold">Stall Unit</th>
                        <th class="font-bold">Issue Summary</th>
                        <th class="font-bold">Status</th>
                        <th class="font-bold">Updated</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($tickets as $t)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono text-xs font-bold text-primary">
                                #TKT-{{ str_pad($t->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="font-bold text-base-content">{{ $t->user->name ?? 'Tenant' }}</div>
                                <div class="text-[11px] text-base-content/50">{{ $t->user->email ?? '' }}</div>
                            </td>
                            <td>
                                @if($t->stall)
                                    <span class="badge badge-neutral font-mono font-bold text-xs">{{ $t->stall->stall_number }}</span>
                                @else
                                    <span class="text-xs text-base-content/40">General</span>
                                @endif
                            </td>
                            <td class="font-medium text-base-content/90 max-w-xs truncate">
                                {{ $t->issue }}
                            </td>
                            <td>
                                <span class="badge badge-sm font-bold uppercase {{ $t->status === 'open' ? 'badge-info text-white' : ($t->status === 'closed' ? 'badge-neutral' : 'badge-warning') }}">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="text-xs text-base-content/60">
                                {{ $t->updated_at->diffForHumans() }}
                            </td>
                            <td class="text-right">
                                <button wire:click="openThreadModal({{ $t->id }})" class="btn btn-primary btn-xs rounded-xl font-bold gap-1 shadow-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    Chat ({{ $t->messages->count() }})
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-12 text-base-content/40 text-xs font-semibold">
                                No support tickets found matching filter criteria.
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
            <div wire:click="toggleWidgetMinimize" class="fixed bottom-4 right-4 z-50 flex items-center gap-3 bg-primary text-primary-content px-4 py-3 rounded-full shadow-2xl cursor-pointer hover:scale-105 transition transform">
                <div class="relative">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-success rounded-full ring-2 ring-primary"></span>
                </div>
                <div class="text-xs font-bold truncate max-w-[140px]">
                    {{ $selectedTicket->user->name ?? 'Tenant' }}
                </div>
                <button wire:click.stop="closeThreadModal" class="btn btn-ghost btn-circle btn-xs text-primary-content hover:bg-white/20">
                    &times;
                </button>
            </div>
        @else
            <!-- Expanded Messenger Chat Box -->
            <div wire:poll.3s="markAsRead" class="fixed bottom-4 right-4 z-50 w-80 md:w-96 h-[500px] bg-base-100 border border-base-300 rounded-3xl shadow-2xl flex flex-col overflow-hidden animate-in slide-in-from-bottom duration-200">
                <!-- Mini Tab Header -->
                <div class="bg-base-200/90 backdrop-blur-md px-4 py-3 border-b border-base-300 flex items-center justify-between">
                    <div class="flex items-center gap-2.5 truncate">
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content rounded-full w-8 h-8 font-bold text-xs">
                                <span>{{ strtoupper(substr($selectedTicket->user->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        </div>
                        <div class="truncate">
                            <div class="font-bold text-xs text-base-content truncate flex items-center gap-1.5">
                                <span>{{ $selectedTicket->user->name ?? 'Tenant' }}</span>
                                <span class="badge badge-xs font-mono font-bold">{{ '#TKT-' . str_pad($selectedTicket->id, 5, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <div class="text-[10px] text-base-content/60 truncate">{{ $selectedTicket->issue }}</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-1">
                        @if($selectedTicket->status === 'open')
                            <button wire:click="updateStatus('closed')" class="btn btn-ghost btn-xs text-[10px] font-bold text-neutral" title="Close Ticket">Close</button>
                        @else
                            <button wire:click="updateStatus('open')" class="btn btn-ghost btn-xs text-[10px] font-bold text-success" title="Reopen Ticket">Reopen</button>
                        @endif
                        <button wire:click="toggleWidgetMinimize" class="btn btn-ghost btn-square btn-xs text-base-content/70" title="Minimize">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </button>
                        <button wire:click="closeThreadModal" class="btn btn-ghost btn-square btn-xs text-base-content/70 hover:text-error" title="Close">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Chat Message Stream -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-base-100">
                    @forelse($selectedTicket->messages as $msg)
                        <div class="chat {{ $msg->user_id === auth()->id() ? 'chat-end' : 'chat-start' }}">
                            <div class="chat-header text-[10px] text-base-content/50 font-semibold mb-0.5">
                                {{ $msg->user->name }}
                                <time class="text-[9px] opacity-60 ml-1">{{ $msg->created_at->format('h:i A') }}</time>
                            </div>
                            <div class="chat-bubble text-xs font-medium {{ $msg->user_id === auth()->id() ? 'chat-bubble-primary text-primary-content' : 'bg-base-200 text-base-content' }}">
                                {{ $msg->message }}
                                @if($msg->attachment_path)
                                    <div class="mt-1.5 pt-1.5 border-t border-current/20">
                                        <a href="{{ route('file.ticket-attachment', $msg->id) }}" target="_blank" class="text-[11px] underline font-bold flex items-center gap-1 opacity-90">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Attachment
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-xs text-base-content/40">No messages in this chat thread yet.</div>
                    @endforelse
                </div>

                <!-- Chat Reply Footer Form -->
                <form wire:submit.prevent="sendMessage" class="p-3 bg-base-200/50 border-t border-base-300 flex items-center gap-2">
                    <input type="text" wire:model="newMessage" placeholder="Type staff response..." required class="input input-sm input-bordered flex-1 rounded-xl text-xs" />
                    <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold px-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>
            </div>
        @endif
    @endif
</div>

