<div class="space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Calendar</h1>
            <p class="text-xs text-base-content/60 mt-1">Lease end dates, invoice due dates and renewal requests. Click an item to open it.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="previousMonth" class="btn btn-sm btn-ghost rounded-xl" aria-label="Previous month">&larr;</button>
            <span class="font-black text-base-content min-w-36 text-center">{{ $monthStart->format('F Y') }}</span>
            <button wire:click="nextMonth" class="btn btn-sm btn-ghost rounded-xl" aria-label="Next month">&rarr;</button>
            <button wire:click="thisMonth" class="btn btn-sm btn-outline rounded-xl font-bold">Today</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 text-xs font-semibold text-base-content/70">
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-info"></span> Lease ends</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-warning"></span> Invoice due</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-error"></span> Overdue</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-accent"></span> Renewal requested</span>
    </div>

    <div class="card bg-base-100 border border-base-300 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[720px]">
                <div class="grid grid-cols-7 bg-base-200/50 border-b border-base-300 text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                        <div class="px-2 py-2 text-center">{{ $weekday }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @for($i = 0; $i < $leadingBlanks; $i++)
                        <div class="min-h-28 border-b border-r border-base-200 bg-base-200/20"></div>
                    @endfor
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = $monthStart->copy()->day($day)->toDateString();
                            $dayEvents = $events[$date] ?? [];
                        @endphp
                        <div class="min-h-28 border-b border-r border-base-200 p-1.5 space-y-1" wire:key="day-{{ $date }}" x-data="{ all: false }">
                            <div class="text-xs font-bold {{ $date === $today ? 'inline-flex w-6 h-6 items-center justify-center rounded-full bg-primary text-primary-content' : 'text-base-content/60' }}">{{ $day }}</div>
                            @foreach($dayEvents as $index => $event)
                                @php
                                    $chip = match (true) {
                                        $event['type'] === 'lease_end' => 'bg-info/15 text-info border-info/30',
                                        $event['type'] === 'renewal' => 'bg-accent/15 text-accent border-accent/30',
                                        $event['overdue'] => 'bg-error/15 text-error border-error/30',
                                        default => 'bg-warning/20 text-base-content border-warning/40',
                                    };
                                @endphp
                                <a href="{{ $event['url'] }}" wire:navigate @if($index >= 3) x-show="all" x-cloak @endif
                                   class="block truncate rounded-md border px-1.5 py-0.5 text-[10px] font-semibold hover:opacity-80 {{ $chip }}" title="{{ $event['label'] }}">
                                    {{ $event['label'] }}
                                </a>
                            @endforeach
                            @if(count($dayEvents) > 3)
                                <button type="button" x-on:click="all = !all" class="text-[10px] font-bold text-primary hover:underline"
                                        x-text="all ? 'Show less' : '+{{ count($dayEvents) - 3 }} more'"></button>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
