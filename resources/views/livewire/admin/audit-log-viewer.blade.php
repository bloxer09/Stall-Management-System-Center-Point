<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">System Audit Logs</h2>
        <p class="text-sm text-gray-500">Chronological history of security events, allocations, and administrative actions.</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 uppercase text-[10px] tracking-wider font-bold">
                    <th class="p-4">Timestamp</th>
                    <th class="p-4">User</th>
                    <th class="p-4">Action</th>
                    <th class="p-4">Entity</th>
                    <th class="p-4">IP Address</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                    <td class="p-4 text-xs text-gray-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $log->user->name ?? 'System/Guest' }}</td>
                    <td class="p-4 font-semibold text-indigo-600 dark:text-indigo-400 text-xs">{{ $log->action }}</td>
                    <td class="p-4 text-xs text-gray-600 dark:text-gray-300">{{ $log->entity_type ? "{$log->entity_type} #{$log->entity_id}" : 'N/A' }}</td>
                    <td class="p-4 text-xs text-gray-400">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="p-8 text-center text-gray-400">No audit logs recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</div>
