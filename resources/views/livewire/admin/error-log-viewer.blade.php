<div class="space-y-6">
    @if(session()->has('message'))
    <div class="p-4 bg-emerald-100 text-emerald-800 rounded-xl text-sm font-medium">{{ session('message') }}</div>
    @endif

    <div class="flex justify-between items-center bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Daily Error Logs</h2>
            <p class="text-sm text-gray-500">Inspect latest system exception traces.</p>
        </div>
        <button wire:click="clearLogs" wire:confirm="Clear error log file?" class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700">
            Clear Logs
        </button>
    </div>

    <div class="bg-gray-900 text-gray-100 p-6 rounded-2xl font-mono text-xs overflow-x-auto max-h-[600px]">
        @forelse($logs as $l)
        <div class="py-1 border-b border-gray-800 whitespace-pre-wrap">{{ $l }}</div>
        @empty
        <div class="text-gray-500 text-center py-8">Log file is empty. No exceptions reported.</div>
        @endforelse
    </div>
</div>
