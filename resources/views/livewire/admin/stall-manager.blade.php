<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black tracking-tight">Stall & Floor Location Management</h2>
            <p class="text-xs text-base-content/60 mt-1">Manage floor level locations, stall assignments, pricing, and grid layouts</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                + Add Stall
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Section 1: Floor Locations Summary Cards -->
    <div class="card bg-base-100 rounded-3xl shadow-md p-6 border border-base-300">
        <h3 class="text-base font-extrabold text-base-content mb-3 flex items-center justify-between">
            <span>Building Floor Levels & Zones</span>
            <span class="text-xs font-normal text-base-content/60">{{ count($locations) }} active floor(s) configured</span>
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($locations as $loc)
                <div class="p-4 rounded-2xl border border-base-300 bg-base-200/50 flex justify-between items-start">
                    <div>
                        <div class="font-bold text-base-content text-sm">{{ $loc->name }}</div>
                        <div class="text-xs text-base-content/60 mt-0.5">{{ $loc->description ?: 'No description' }}</div>
                        <div class="text-[11px] text-base-content/50 mt-2 font-mono">
                            Grid: {{ $loc->grid_rows }}x{{ $loc->grid_cols }} &bull; Stalls: {{ $loc->stalls_count ?? $loc->stalls()->count() }}
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button wire:click="openLocationModal({{ $loc->id }})" class="btn btn-ghost btn-xs text-primary font-bold">Edit</button>
                        <button wire:click="deleteLocation({{ $loc->id }})" wire:confirm="Are you sure you want to delete floor location '{{ $loc->name }}'?" class="btn btn-ghost btn-xs text-error font-bold">Delete</button>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-6 text-xs text-base-content/40 font-semibold">No floor locations added yet. Click "+ Add Floor" above to create one.</div>
            @endforelse
        </div>
    </div>

    <!-- Section 2: Stall Table with Filters -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search stall number..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="locationFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Floor Levels</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Stall Number</th>
                        <th class="font-bold">Floor Level</th>
                        <th class="font-bold">Monthly Price</th>
                        <th class="font-bold">Grid Position</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($stalls as $stall)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-bold text-base-content">{{ $stall->stall_number }}</td>
                            <td>
                                @if($stall->location)
                                    <span class="badge badge-primary font-bold text-xs">{{ $stall->location->name }}</span>
                                @else
                                    <span class="text-base-content/40 text-xs">Unassigned</span>
                                @endif
                            </td>
                            <td class="font-bold text-base-content">₱{{ number_format($stall->price, 2) }}</td>
                            <td class="text-base-content/60 text-xs font-mono">Row {{ $stall->grid_row ?? '-' }}, Col {{ $stall->grid_col ?? '-' }}</td>
                            <td>
                                <span class="badge badge-sm font-bold uppercase {{ $stall->status === 'available' ? 'badge-success text-white' : ($stall->status === 'occupied' ? 'badge-info text-white' : 'badge-warning') }}">
                                    {{ $stall->status }}
                                </span>
                            </td>
                            <td class="text-right space-x-1">
                                <button wire:click="openModal({{ $stall->id }})" class="btn btn-ghost btn-xs text-primary font-bold">Edit</button>
                                <button wire:click="deleteStall({{ $stall->id }})" wire:confirm="Are you sure you want to delete stall {{ $stall->stall_number }}?" class="btn btn-ghost btn-xs text-error font-bold">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-base-content/40 text-xs font-semibold">No stalls found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $stalls->links() }}
        </div>
    </div>

    <!-- Modal 1: Stall Add/Edit Modal -->
    @if($showModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4">
                <h3 class="text-lg font-bold text-base-content">{{ $stallId ? 'Edit Stall Details' : 'Add New Stall' }}</h3>
                <form wire:submit.prevent="saveStall" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Stall Number *</span></label>
                        <input type="text" wire:model="stall_number" class="input input-bordered rounded-xl text-sm" placeholder="e.g. GF-101">
                        @error('stall_number') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Floor Location / Zone</span></label>
                        <select wire:model="location_id" class="select select-bordered rounded-xl text-sm">
                            <option value="">-- Select Floor Level --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Monthly Price (₱) *</span></label>
                        <input type="number" step="0.01" wire:model="price" class="input input-bordered rounded-xl text-sm" placeholder="5000.00">
                        @error('price') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Occupancy Status *</span></label>
                        <select wire:model="status" class="select select-bordered rounded-xl text-sm">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                        @error('status') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Description / Notes</span></label>
                        <textarea wire:model="description" rows="2" class="textarea textarea-bordered rounded-xl text-sm" placeholder="Brief unit description..."></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold">Save Stall</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Floor Location Add/Edit Modal -->
    @if($showLocationModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4">
                <h3 class="text-lg font-bold text-base-content">{{ $editingLocationId ? 'Edit Floor Location' : 'Add New Floor Location' }}</h3>
                <form wire:submit.prevent="saveLocation" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Floor Name *</span></label>
                        <input type="text" wire:model="location_name" class="input input-bordered rounded-xl text-sm" placeholder="e.g. 3rd Floor Mezzanine">
                        @error('location_name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Description</span></label>
                        <textarea wire:model="location_description" rows="2" class="textarea textarea-bordered rounded-xl text-sm" placeholder="Description..."></textarea>
                        @error('location_description') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Sort Order</span></label>
                            <input type="number" wire:model="location_sort_order" class="input input-bordered rounded-xl text-sm">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Grid Rows</span></label>
                            <input type="number" wire:model="location_grid_rows" class="input input-bordered rounded-xl text-sm">
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-[10px] uppercase">Grid Cols</span></label>
                            <input type="number" wire:model="location_grid_cols" class="input input-bordered rounded-xl text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeLocationModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-success text-white rounded-xl font-bold">Save Floor Location</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeLocationModal">close</button></form>
        </dialog>
    @endif
</div>

