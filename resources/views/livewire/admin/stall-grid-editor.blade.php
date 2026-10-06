<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Floor Map & Space Planner</h1>
            <p class="text-xs text-base-content/60 mt-1">Interactive visual grid to arrange and manage stalls on floor levels.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stalls') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                Stalls Catalog
            </a>
        </div>
    </div>

    <!-- Floor Map Card with Integrated Controls -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <!-- Control & Floor Selection Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <span class="text-xs font-bold uppercase tracking-wider text-base-content/70">Floor Level:</span>
                <select wire:model.live="selectedLocationId" class="select select-bordered rounded-2xl text-sm font-bold text-base-content w-full md:w-auto">
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->grid_rows }}x{{ $loc->grid_cols }} Grid)</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-base-content/70">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span> Available</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-warning inline-block"></span> Reserved</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-info inline-block"></span> Occupied</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-error inline-block"></span> Maintenance</span>
            </div>
        </div>

        <!-- Unassigned Stalls Picker -->
        @if(count($unassignedStalls) > 0)
        <div class="p-4 bg-primary/5 border-b border-base-300 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Unassigned Stalls (drag a stall onto an empty cell, or select it and click a cell):
                </span>
                <span class="badge badge-primary badge-sm font-bold">{{ count($unassignedStalls) }} Unassigned</span>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                @foreach($unassignedStalls as $st)
                <button wire:click="$set('selectedStallId', {{ $st->id }})" draggable="true"
                    @dragstart="$event.dataTransfer.setData('text/plain', '{{ $st->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                    class="cursor-grab active:cursor-grabbing btn btn-sm rounded-xl text-xs font-bold transition {{ $selectedStallId == $st->id ? 'btn-primary shadow-xs ring-2 ring-primary ring-offset-2' : 'btn-outline border-base-300 bg-base-100 hover:bg-base-200' }}">
                    Stall {{ $st->stall_number }} (₱{{ number_format($st->price, 2) }})
                </button>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Interactive Grid Canvas -->
        <div class="p-6 overflow-x-auto bg-base-100">
            <div class="inline-grid gap-3 min-w-full justify-center" style="grid-template-columns: repeat({{ $gridCols }}, minmax(110px, 1fr));">
                @for($r = 1; $r <= $gridRows; $r++)
                    @for($c = 1; $c <= $gridCols; $c++)
                        @php $key = "{$r}_{$c}"; $stall = $gridStalls[$key] ?? null; @endphp
                        <div wire:click="placeStallOnGrid({{ $r }}, {{ $c }})"
                            @if(!$stall)
                                x-data="{ over: false }"
                                @dragover.prevent="over = true"
                                @dragleave="over = false"
                                @drop.prevent="over = false; $wire.moveStall(parseInt($event.dataTransfer.getData('text/plain')), {{ $r }}, {{ $c }})"
                                :class="over && 'ring-2 ring-primary bg-primary/10'"
                            @else
                                draggable="true"
                                @dragstart="$event.dataTransfer.setData('text/plain', '{{ $stall->id }}'); $event.dataTransfer.effectAllowed = 'move'"
                            @endif
                            wire:key="cell-{{ $r }}-{{ $c }}"
                            class="h-28 p-3 rounded-2xl border-2 border-dashed flex flex-col justify-between items-center transition cursor-pointer relative group {{ $stall ? ($stall->status === 'occupied' ? 'bg-info/10 border-info text-info-content shadow-xs' : ($stall->status === 'reserved' ? 'bg-warning/10 border-warning shadow-xs' : ($stall->status === 'maintenance' ? 'bg-error/10 border-error shadow-xs' : 'bg-primary/10 border-primary shadow-xs'))) : 'border-base-300 hover:border-primary hover:bg-base-200/50 bg-base-200/20' }}">
                            <span class="text-[10px] text-base-content/40 font-mono self-start font-bold">R{{ $r }}C{{ $c }}</span>
                            @if($stall)
                            <div class="text-center w-full">
                                <span class="text-xs font-black text-base-content block truncate" title="Stall {{ $stall->stall_number }}">Stall {{ $stall->stall_number }}</span>
                                <span class="badge badge-xs font-bold uppercase mt-1 {{ $stall->status === 'available' ? 'badge-primary' : ($stall->status === 'occupied' ? 'badge-info text-white' : ($stall->status === 'reserved' ? 'badge-warning text-slate-900' : 'badge-error text-white')) }}">
                                    {{ $stall->status }}
                                </span>
                            </div>
                            <button wire:click.stop="removeStallFromGrid({{ $stall->id }})" title="Remove from grid cell" aria-label="Remove Stall {{ $stall->stall_number }} from grid" class="absolute top-1.5 right-1.5 btn btn-ghost btn-xs btn-circle text-error opacity-0 group-hover:opacity-100 transition hover:bg-error/10">
                                ✕
                            </button>
                            @else
                            <span class="text-xs text-base-content/40 font-bold my-auto group-hover:text-primary transition">+ Place</span>
                            @endif
                        </div>
                    @endfor
                @endfor
            </div>
        </div>
    </div>
</div>
