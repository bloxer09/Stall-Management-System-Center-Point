<div class="dropdown dropdown-end" wire:poll.10s>
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
        <div class="indicator">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="badge badge-sm badge-error indicator-item text-white font-bold">{{ $unreadCount }}</span>
            @endif
        </div>
    </div>
    <div tabindex="0" class="mt-3 z-[1] card card-compact dropdown-content w-80 bg-base-100 shadow-2xl border border-base-300">
        <div class="card-body">
            <div class="flex items-center justify-between border-b border-base-200 pb-2">
                <h3 class="font-extrabold text-sm">Notifications</h3>
                @if($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="btn btn-xs btn-ghost text-primary font-bold">Mark all read</button>
                @endif
            </div>

            <div class="max-h-64 overflow-y-auto space-y-2 py-1">
                @forelse($notifications as $n)
                    <div wire:click="markAsRead({{ data_get($n, 'id') }})" class="p-2.5 rounded-xl transition cursor-pointer text-xs {{ data_get($n, 'is_read') ? 'bg-base-100 opacity-60' : 'bg-primary/10 border-l-4 border-primary font-semibold' }}">
                        <div class="font-bold text-base-content">{{ data_get($n, 'title') }}</div>
                        <div class="text-base-content/80 mt-0.5">{{ data_get($n, 'message') }}</div>
                        <div class="text-[10px] text-base-content/50 mt-1">{{ \Carbon\Carbon::parse(data_get($n, 'created_at'))->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-base-content/50">
                        No notifications found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
