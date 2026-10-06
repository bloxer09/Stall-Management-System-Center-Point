<div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Floors & Locations</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage building floor levels and layout spaces.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Floor
        </button>
    </div>

    <!-- Data Table Container -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <!-- Search Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search floor name..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="text-xs text-base-content/60 font-medium">
                Total Floors: <strong class="text-base-content">{{ $floors->total() }}</strong>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Order</th>
                        <th class="font-bold">Floor Level Name</th>
                        <th class="font-bold">Description</th>
                        <th class="font-bold">Total Stalls</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($floors as $floor)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono text-sm font-bold text-base-content/70">#{{ $floor->sort_order }}</td>
                            <td class="font-bold text-base-content flex items-center gap-2 py-3">
                                <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-sm font-black">
                                    {{ strtoupper(substr($floor->name, 0, 1)) }}
                                </div>
                                <span class="text-base font-extrabold text-base-content">{{ $floor->name }}</span>
                            </td>
                            <td class="text-sm text-base-content/80 font-medium max-w-md">{{ $floor->description ?: 'No description provided.' }}</td>
                            <td>
                                <span class="badge badge-md badge-primary font-bold text-xs">
                                    {{ $floor->stalls_count }} Stalls
                                </span>
                            </td>
                            <td class="text-right">
                                <button wire:click="openDetailModal({{ $floor->id }})" class="btn btn-ghost btn-xs text-primary font-bold rounded-lg border border-primary/20 hover:bg-primary hover:text-white transition">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-base-content/40 text-xs font-semibold">
                                No floor levels found. Click "+ Add Floor" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-base-300">
            {{ $floors->links() }}
        </div>
    </div>

    <!-- Modal 1: Floor Detail & Actions Modal -->
    @if($showDetailModal && $selectedFloor)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="$wire.closeDetailModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-lg font-black">
                        {{ strtoupper(substr($selectedFloor->name, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-base-content">{{ $selectedFloor->name }}</h3>
                        <p class="text-xs text-base-content/60">Display Order #{{ $selectedFloor->sort_order }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-3 bg-base-200/60 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <div class="text-[10px] font-extrabold uppercase text-base-content/60">Total Stalls</div>
                        <div class="font-bold text-sm text-base-content mt-0.5">{{ $selectedFloor->stalls_count }} Stalls</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-extrabold uppercase text-base-content/60">Grid Dimensions</div>
                        <div class="font-bold text-sm text-primary font-mono mt-0.5">{{ $selectedFloor->grid_rows }} &times; {{ $selectedFloor->grid_cols }}</div>
                    </div>
                </div>

                @if($selectedFloor->description)
                    <div class="p-3 bg-base-200/30 rounded-2xl border border-base-300/50 text-xs text-base-content/80">
                        <span class="font-bold text-[10px] uppercase text-base-content/60 block mb-0.5">Description</span>
                        {{ $selectedFloor->description }}
                    </div>
                @endif

                <div class="flex items-center justify-between pt-3 border-t border-base-200">
                    <button wire:click="deleteFloor({{ $selectedFloor->id }})" wire:confirm="Are you sure you want to delete '{{ $selectedFloor->name }}'?" class="btn btn-error btn-outline btn-sm rounded-xl font-bold gap-1" wire:loading.attr="disabled" wire:target="deleteFloor"><span wire:loading wire:target="deleteFloor" class="loading loading-spinner loading-xs"></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Delete
                    </button>
                    <div class="flex gap-2">
                        <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                        <button wire:click="openModal({{ $selectedFloor->id }})" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Floor
                        </button>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Create/Edit Form Modal Dialog -->
    @if($showModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
                <div class="flex justify-between items-center pb-2 border-b border-base-200">
                    <h3 class="text-lg font-bold text-base-content">{{ $floorId ? 'Edit Floor Level' : 'Add New Floor Level' }}</h3>
                    <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost" aria-label="Close" x-data x-on:keydown.escape.window="$wire.closeModal()">&times;</button>
                </div>

                <form wire:submit.prevent="saveFloor" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Floor Level Name *</span></label>
                        <input type="text" wire:model="name" class="input input-bordered rounded-xl text-sm" placeholder="e.g. 4th Floor Tech Hub">
                        @error('name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Description</span></label>
                        <textarea wire:model="description" rows="2" class="textarea textarea-bordered rounded-xl text-sm" placeholder="Description..."></textarea>
                        @error('description') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Sort Order *</span></label>
                            <input type="number" wire:model="sort_order" class="input input-bordered rounded-xl text-sm">
                            @error('sort_order') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Grid Rows *</span></label>
                            <input type="number" wire:model="grid_rows" class="input input-bordered rounded-xl text-sm">
                            @error('grid_rows') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Grid Cols *</span></label>
                            <input type="number" wire:model="grid_cols" class="input input-bordered rounded-xl text-sm">
                            @error('grid_cols') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold" wire:loading.attr="disabled" wire:target="saveFloor"><span wire:loading wire:target="saveFloor" class="loading loading-spinner loading-xs"></span>Save Floor Level</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
        </dialog>
    @endif
</div>
