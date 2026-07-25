<div class="space-y-6">
    <!-- Header & Action Button -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight">Floor Level Management</h1>
            <p class="text-xs text-base-content/60 mt-1">Configure commercial building floor levels, descriptions, sort orders, and grid plan dimensions</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Floor Level
        </button>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

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
                        <th class="font-bold">Sort</th>
                        <th class="font-bold">Floor Level Name</th>
                        <th class="font-bold">Description</th>
                        <th class="font-bold">Grid Dimensions</th>
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
                            <td class="font-mono text-sm text-primary font-bold">
                                {{ $floor->grid_rows }} Rows &times; {{ $floor->grid_cols }} Cols
                            </td>
                            <td>
                                <span class="badge badge-md badge-primary font-bold text-xs">
                                    {{ $floor->stalls_count }} Stalls
                                </span>
                            </td>
                            <td class="text-right space-x-1">
                                <button wire:click="openModal({{ $floor->id }})" class="btn btn-ghost btn-sm text-primary font-bold">
                                    Edit
                                </button>
                                <button wire:click="deleteFloor({{ $floor->id }})" wire:confirm="Are you sure you want to delete '{{ $floor->name }}'?" class="btn btn-ghost btn-sm text-error font-bold">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-base-content/40 text-xs font-semibold">
                                No floor levels found. Click "+ Add Floor Level" to create one.
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

    <!-- Create/Edit Modal Dialog -->
    @if($showModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4">
                <div class="flex justify-between items-center pb-2 border-b border-base-200">
                    <h3 class="text-lg font-bold text-base-content">{{ $floorId ? 'Edit Floor Level' : 'Add New Floor Level' }}</h3>
                    <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost">&times;</button>
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
                        <button type="submit" class="btn btn-primary rounded-xl font-bold">Save Floor Level</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
        </dialog>
    @endif
</div>

