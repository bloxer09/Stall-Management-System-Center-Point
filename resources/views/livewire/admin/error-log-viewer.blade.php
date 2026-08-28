<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Error Logs</h1>
            <p class="text-xs text-base-content/60 mt-1">View error logs and system diagnostics.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportXlsx"
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-wait" wire:target="exportXlsx"
                class="btn btn-outline btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <span wire:loading wire:target="exportXlsx" class="loading loading-spinner loading-xs"></span>
                <svg class="w-4 h-4" wire:loading.remove wire:target="exportXlsx" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel (.xlsx)
            </button>
            <button wire:click="clearLogs" wire:confirm="Are you sure you want to clear all error logs?"
                wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-wait" wire:target="clearLogs"
                class="btn btn-error btn-outline btn-sm rounded-xl font-bold gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Clear Log File
            </button>
        </div>
    </div>

    <!-- Structured Error Logs Data Table Container -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md">
        <!-- Search & Category Filter Toolbar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search exception message or class..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <select wire:model.live="categoryFilter" class="select select-bordered select-sm rounded-xl text-xs font-bold">
                    <option value="">-- All Error Categories --</option>
                    <option value="Database / Query">Database / Query</option>
                    <option value="Auth / Security">Auth / Security</option>
                    <option value="HTTP / Route">HTTP / Route</option>
                    <option value="Validation / Input">Validation / Input</option>
                    <option value="Payment / Gateway">Payment / Gateway</option>
                    <option value="System / General">System / General</option>
                </select>
                <div class="text-xs text-base-content/70 font-semibold">
                    Total Errors: <strong class="text-base-content">{{ $totalCount }}</strong>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 font-bold uppercase text-xs">
                        <th>Timestamp</th>
                        <th>Error Category</th>
                        <th>Exception Class</th>
                        <th>Message Preview</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-base-200/40 transition">
                        <td class="font-mono text-xs text-base-content/70 font-semibold whitespace-nowrap">{{ $log['timestamp'] }}</td>
                        <td>
                            @php
                                $badgeClass = match($log['category']) {
                                    'Database / Query' => 'badge-error text-white',
                                    'Auth / Security' => 'badge-warning',
                                    'HTTP / Route' => 'badge-info text-white',
                                    'Payment / Gateway' => 'badge-primary',
                                    default => 'badge-neutral'
                                };
                            @endphp
                            <span class="badge badge-sm font-extrabold text-[10px] {{ $badgeClass }}">{{ $log['category'] }}</span>
                        </td>
                        <td class="font-mono text-xs font-bold text-base-content max-w-xs truncate" title="{{ $log['exception_class'] }}">
                            {{ $log['exception_class'] }}
                        </td>
                        <td class="text-xs text-base-content/80 max-w-md truncate font-mono">
                            {{ $log['message'] }}
                        </td>
                        <td class="text-right">
                            <button wire:click="openTraceModal({{ $log['id'] }})" class="btn btn-ghost btn-xs text-primary font-bold">
                                Inspect Trace
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-base-content/40">
                                <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p class="font-extrabold text-sm">No error logs found</p>
                                    <p class="text-xs mt-0.5">The system is clean — no exceptions match your search criteria.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-base-300">
            {{ $logs->links() }}
        </div>
    </div>

    <!-- Stack Trace Inspection Modal -->
    @if($showTraceModal && $selectedLog)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-3xl p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between border-b border-base-200 pb-3">
                <div>
                    <span class="badge badge-error text-white text-[10px] font-extrabold uppercase">{{ $selectedLog['category'] }}</span>
                    <h3 class="text-base font-black text-base-content mt-1">{{ $selectedLog['exception_class'] }}</h3>
                    <p class="text-xs font-mono text-base-content/60">{{ $selectedLog['timestamp'] }} &bull; Env: {{ $selectedLog['environment'] }}</p>
                </div>
                <button wire:click="closeTraceModal" class="btn btn-ghost btn-circle btn-sm">&times;</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-[11px] font-bold text-base-content/60 uppercase block mb-1">Message</label>
                    <div class="p-3 bg-base-200 rounded-xl text-xs font-mono text-error font-semibold break-all">
                        {{ $selectedLog['message'] }}
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-base-content/60 uppercase block mb-1">Full Stack Trace</label>
                    <pre class="p-4 bg-neutral text-neutral-content rounded-2xl text-[11px] font-mono overflow-x-auto max-h-80 leading-relaxed whitespace-pre-wrap">{{ $selectedLog['stack_trace'] }}</pre>
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-base-200">
                <button wire:click="closeTraceModal" class="btn btn-primary btn-sm rounded-xl font-bold">Close Inspector</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeTraceModal">close</button></form>
    </dialog>
    @endif
</div>
