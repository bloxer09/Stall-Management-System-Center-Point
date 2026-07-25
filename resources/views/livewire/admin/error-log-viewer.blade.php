<div class="space-y-6">
    @if(session()->has('message'))
    <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('message') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">Daily Exception Traces</h2>
            <p class="text-xs text-base-content/60 mt-1">Inspect and search system exception logs and error stack traces.</p>
        </div>
        <button wire:click="clearLogs" wire:confirm="Clear error log file?" class="btn btn-error btn-sm rounded-xl font-bold gap-1 text-white shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Clear Log File
        </button>
    </div>

    <!-- Error Logs Card Container -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md space-y-4">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search error trace keyword or status code..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="text-xs text-base-content/70 font-semibold">
                Total Trace Lines: <strong class="text-base-content">{{ $logs->total() }}</strong>
            </div>
        </div>

        <!-- Log Output Box -->
        <div class="p-4">
            <div class="bg-base-200/70 border border-base-300 rounded-2xl p-4 font-mono text-xs text-base-content space-y-2 overflow-x-auto max-h-[500px]">
                @forelse($logs as $l)
                <div class="py-1.5 border-b border-base-300/50 whitespace-pre-wrap leading-relaxed">{{ $l }}</div>
                @empty
                <div class="text-base-content/40 text-center py-10 font-semibold">Log file is empty or no lines match search query.</div>
                @endforelse
            </div>
        </div>

        <div class="p-4 border-t border-base-300">{{ $logs->links() }}</div>
    </div>
</div>
