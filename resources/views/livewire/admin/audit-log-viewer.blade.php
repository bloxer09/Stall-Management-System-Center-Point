<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">System Audit Logs</h2>
            <p class="text-xs text-base-content/60 mt-1">Chronological history of security events, allocations, and administrative actions.</p>
        </div>
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
                        <td class="font-extrabold text-base-content">{{ $log->user->name ?? 'System/Guest' }}</td>
                        <td class="font-bold text-primary text-xs">{{ $log->action }}</td>
                        <td class="text-xs text-base-content/80 font-medium">{{ $log->entity_type ? "{$log->entity_type} #{$log->entity_id}" : 'N/A' }}</td>
                        <td class="text-right font-mono text-xs text-base-content/60">{{ $log->ip_address }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="p-8 text-center text-base-content/40 text-xs font-semibold">No audit logs found matching criteria.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">{{ $logs->links() }}</div>
    </div>
</div>
