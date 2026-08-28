<div class="space-y-6">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Floor Map Editor</h1>
            <p class="text-xs text-base-content/60 mt-1">Arrange stalls on the floor map.</p>
        </div>
        <div class="flex items-center gap-3">
            <label class="text-xs font-bold uppercase tracking-wider text-base-content/60">Floor:</label>
            <select wire:model.live="selectedLocationId" class="select select-bordered rounded-2xl text-sm">
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->grid_rows }}x{{ $loc->grid_cols }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Unassigned Stalls Picker -->
    @if(count($unassignedStalls) > 0)
    <div class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-primary block">Unassigned Stalls (Select a stall then click a grid cell below):</span>
        <div class="flex flex-wrap gap-2">
            @foreach($unassignedStalls as $st)
            <button wire:click="$set('selectedStallId', {{ $st->id }})" class="btn btn-sm rounded-xl text-xs font-bold transition {{ $selectedStallId == $st->id ? 'btn-primary shadow-xs' : 'btn-outline border-base-300' }}">
                Stall {{ $st->stall_number }} (₱{{ number_format($st->price) }})
            </button>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Interactive Grid Area -->
    <div class="card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md overflow-x-auto">
        <div class="inline-grid gap-2" style="grid-template-columns: repeat({{ $gridCols }}, minmax(90px, 1fr));">
            @for($r = 1; $r <= $gridRows; $r++)
                @for($c = 1; $c <= $gridCols; $c++)
                    @php $key = "{$r}_{$c}"; $stall = $gridStalls[$key] ?? null; @endphp
                    <div wire:click="placeStallOnGrid({{ $r }}, {{ $c }})" class="h-24 p-2 rounded-2xl border-2 border-dashed flex flex-col justify-between items-center transition cursor-pointer relative group {{ $stall ? ($stall->status === 'occupied' ? 'bg-success/10 border-success' : ($stall->status === 'maintenance' ? 'bg-warning/10 border-warning' : 'bg-primary/10 border-primary')) : 'border-base-300 hover:border-primary bg-base-200/30' }}">
                        <span class="text-[10px] text-base-content/40 font-mono self-start">R{{ $r }}C{{ $c }}</span>
                        @if($stall)
                        <div class="text-center">
                            <span class="text-xs font-extrabold text-base-content block">Stall {{ $stall->stall_number }}</span>
                            <span class="badge badge-xs font-bold uppercase mt-1 {{ $stall->status === 'occupied' ? 'badge-success text-white' : ($stall->status === 'maintenance' ? 'badge-warning' : 'badge-primary') }}">
                                {{ $stall->status }}
                            </span>
                        </div>
                        <button wire:click.stop="removeStallFromGrid({{ $stall->id }})" class="absolute top-1 right-1 btn btn-ghost btn-xs btn-circle text-error opacity-0 group-hover:opacity-100 transition">
                            ✕
                        </button>
                        @else
                        <span class="text-xs text-base-content/40 font-semibold my-auto">+ Assign</span>
                        @endif
                    </div>
                @endfor
            @endfor
        </div>
    </div>
</div>
