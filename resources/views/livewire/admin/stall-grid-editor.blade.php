<div class="space-y-6">
    @if(session()->has('message'))
    <div class="p-4 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-xl text-sm font-medium">
        {{ session('message') }}
    </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Visual Floor Plan Grid Editor</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Position stalls onto the interactive floor plan layout.</p>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-xs font-semibold text-gray-500 uppercase">Floor Location:</label>
            <select wire:model.live="selectedLocationId" class="rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm text-gray-900 dark:text-white">
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->grid_rows }}x{{ $loc->grid_cols }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Unassigned Stalls Picker -->
    @if(count($unassignedStalls) > 0)
    <div class="p-4 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-100 dark:border-indigo-800/50">
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 block mb-2">Unassigned Stalls (Select a stall then click a grid cell below):</span>
        <div class="flex flex-wrap gap-2">
            @foreach($unassignedStalls as $st)
            <button wire:click="$set('selectedStallId', {{ $st->id }})" class="px-3 py-1.5 rounded-lg text-xs font-bold border transition {{ $selectedStallId == $st->id ? 'bg-indigo-600 text-white border-indigo-600 shadow' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-indigo-200 hover:border-indigo-500' }}">
                Stall {{ $st->stall_number }} (₱{{ number_format($st->price) }})
            </button>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Interactive Grid Area -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-x-auto">
        <div class="inline-grid gap-2" style="grid-template-columns: repeat({{ $gridCols }}, minmax(90px, 1fr));">
            @for($r = 1; $r <= $gridRows; $r++)
                @for($c = 1; $c <= $gridCols; $c++)
                    @php $key = "{$r}_{$c}"; $stall = $gridStalls[$key] ?? null; @endphp
                    <div wire:click="placeStallOnGrid({{ $r }}, {{ $c }})" class="h-24 p-2 rounded-xl border-2 border-dashed flex flex-col justify-between items-center transition cursor-pointer relative group {{ $stall ? ($stall->status === 'occupied' ? 'bg-emerald-500/10 border-emerald-500' : ($stall->status === 'maintenance' ? 'bg-amber-500/10 border-amber-500' : 'bg-indigo-500/10 border-indigo-500')) : 'border-gray-200 dark:border-gray-700 hover:border-indigo-400 bg-gray-50/50 dark:bg-gray-700/20' }}">
                        <span class="text-[10px] text-gray-400 self-start">R{{ $r }}C{{ $c }}</span>
                        @if($stall)
                        <div class="text-center">
                            <span class="text-xs font-extrabold text-gray-900 dark:text-white block">Stall {{ $stall->stall_number }}</span>
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded text-white mt-1 inline-block {{ $stall->status === 'occupied' ? 'bg-emerald-600' : ($stall->status === 'maintenance' ? 'bg-amber-600' : 'bg-indigo-600') }}">
                                {{ $stall->status }}
                            </span>
                        </div>
                        <button wire:click.stop="removeStallFromGrid({{ $stall->id }})" class="absolute top-1 right-1 text-red-500 opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 rounded">
                            ✕
                        </button>
                        @else
                        <span class="text-xs text-gray-400 my-auto">+ Assign</span>
                        @endif
                    </div>
                @endfor
            @endfor
        </div>
    </div>
</div>
