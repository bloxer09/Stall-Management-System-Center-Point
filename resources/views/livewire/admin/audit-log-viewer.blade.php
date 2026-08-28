<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">System Audit Logs</h1>
            <p class="text-xs text-base-content/60 mt-1">Chronological history of security events, allocations, and administrative actions.</p>
        </div>
        <button wire:click="exportXlsx"
            wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-wait" wire:target="exportXlsx"
            class="btn btn-outline btn-sm rounded-xl font-bold gap-1 shadow-xs">
            <span wire:loading wire:target="exportXlsx" class="loading loading-spinner loading-xs"></span>
            <svg class="w-4 h-4" wire:loading.remove wire:target="exportXlsx" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Excel (.xlsx)
        </button>
    </div>

    <!-- Audit Logs Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search action, user, or IP address..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="text-xs text-base-content/70 font-semibold">
                Total Logs: <strong class="text-base-content">{{ $logs->total() }}</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 font-bold uppercase text-xs">
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th class="text-right">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-base-200/40 transition">
                        <td class="font-mono text-xs text-base-content/70 font-semibold">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                @if($log->user)
                                    <div class="avatar">
                                        <div class="w-7 h-7 rounded-full overflow-hidden border border-primary/20 shadow-xs">
                                            <img src="{{ $log->user->avatar_url }}" onclick="openImageLightbox(this.src)" class="w-full h-full object-cover cursor-pointer" alt="User Avatar" title="Click to view full photo">
                                        </div>
                                    </div>
                                @endif
                                <span class="font-extrabold text-base-content">{{ $log->user->name ?? 'System/Guest' }}</span>
                            </div>
                        </td>
                        <td class="font-bold text-primary text-xs">{{ $log->action }}</td>
                        <td class="text-xs text-base-content/80 font-medium">{{ $log->entity_type ? "{$log->entity_type} #{$log->entity_id}" : 'N/A' }}</td>
                        <td class="text-right font-mono text-xs text-base-content/60">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-base-content/40">
                                <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p class="font-extrabold text-sm">No audit logs found</p>
                                    <p class="text-xs mt-0.5">System events will appear here as actions are performed.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">{{ $logs->links() }}</div>
    </div>
</div>
