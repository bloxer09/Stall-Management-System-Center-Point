<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Stalls & Spaces</h1>
            <p class="text-xs text-base-content/60 mt-1">View and manage stalls, pricing, and availability.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openModal" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-wait"
                wire:target="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Stall
            </button>
        </div>
    </div>

    <!-- Stall Table with Filters -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <!-- Search & Filter Bar -->
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search stall number..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
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

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,locationFilter,statusFilter">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Photo</th>
                        <th class="font-bold">Stall Number</th>
                        <th class="font-bold">Floor Level</th>
                        <th class="font-bold">Monthly Price</th>
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($stalls as $stall)
                        <tr class="hover:bg-base-200/40 transition">
                            <td>
                                <img src="{{ $stall->image_url }}" onclick="openImageLightbox(this.src)"
                                    class="w-12 h-12 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-110 transition"
                                    alt="Stall Photo" title="Click to view full image">
                            </td>
                            <td class="font-extrabold text-base-content">{{ $stall->stall_number }}</td>
                            <td>
                                @if($stall->location)
                                    <span class="badge badge-primary font-bold text-xs">{{ $stall->location->name }}</span>
                                @else
                                    <span class="text-base-content/40 text-xs font-medium">Unassigned</span>
                                @endif
                            </td>
                            <td class="font-bold text-base-content">₱{{ number_format($stall->price, 2) }}</td>
                            <td>
                                @if($stall->currentLease && $stall->currentLease->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full overflow-hidden border border-primary/20 shrink-0">
                                            <img src="{{ $stall->currentLease->user->avatar_url }}" class="w-full h-full object-cover" alt="Tenant Avatar">
                                        </div>
                                        <span class="font-bold text-xs text-base-content truncate max-w-[130px]" title="{{ $stall->currentLease->user->name }}">
                                            {{ $stall->currentLease->user->name }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-xs text-base-content/40 font-medium">None</span>
                                @endif
                            </td>
                            <td>
                                <span
                                    class="badge badge-sm font-bold uppercase {{ $stall->status === 'available' ? 'badge-success text-white' : ($stall->status === 'occupied' ? 'badge-info text-white' : ($stall->status === 'reserved' ? 'badge-warning text-slate-900' : 'badge-error text-white')) }}">
                                    {{ $stall->status }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button wire:click="openDetailModal({{ $stall->id }})"
                                    class="btn btn-ghost btn-xs text-primary font-bold rounded-lg border border-primary/20 hover:bg-primary hover:text-white transition">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No stalls found</p>
                                        <p class="text-xs mt-0.5">Try adjusting your filters or add a new stall unit.</p>
                                    </div>
                                    <button wire:click="openModal"
                                        class="btn btn-primary btn-xs rounded-xl font-bold mt-1">+ Add First Stall</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $stalls->links() }}
        </div>
    </div>

    <!-- Modal 1: Stall Detail & Management Actions Modal -->
    @if($showDetailModal && $selectedStall)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex items-start gap-4">
                    <img src="{{ $selectedStall->image_url }}" onclick="openImageLightbox(this.src)"
                        class="w-20 h-20 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-105 transition"
                        alt="Stall Photo" title="Click to view full image">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-extrabold text-base-content">Stall {{ $selectedStall->stall_number }}</h3>
                            <span class="badge badge-sm font-bold uppercase {{ $selectedStall->status === 'available' ? 'badge-success text-white' : ($selectedStall->status === 'occupied' ? 'badge-info text-white' : 'badge-warning') }}">
                                {{ $selectedStall->status }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-primary">
                            {{ $selectedStall->location->name ?? 'No Floor Assigned' }} &bull; ₱{{ number_format($selectedStall->price, 2) }} / month
                        </p>
                        @if($selectedStall->description)
                            <p class="text-xs text-base-content/70 line-clamp-2">{{ $selectedStall->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Current Tenant Info Card -->
                <div class="p-4 bg-base-200/60 rounded-2xl border border-base-300 space-y-2">
                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Current Tenant</div>
                    @if($selectedStall->currentLease && $selectedStall->currentLease->user)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <img src="{{ $selectedStall->currentLease->user->avatar_url }}" class="w-9 h-9 rounded-full object-cover border border-primary/20" alt="Avatar">
                                <div>
                                    <div class="font-extrabold text-sm text-base-content">{{ $selectedStall->currentLease->user->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ $selectedStall->currentLease->user->email }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-xs badge-success text-white font-bold uppercase">Active Lease</span>
                                <div class="text-[10px] text-base-content/60 font-mono mt-0.5">
                                    Until {{ $selectedStall->currentLease->end_date->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-xs text-base-content/60 py-1">
                            <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            This stall unit is vacant and ready for lease assignment.
                        </div>
                    @endif
                </div>

                <!-- Modal Actions Footer -->
                <div class="flex items-center justify-between pt-3 border-t border-base-200">
                    <button wire:click="deleteStall({{ $selectedStall->id }})" wire:confirm="Are you sure you want to delete stall {{ $selectedStall->stall_number }}?" class="btn btn-error btn-outline btn-sm rounded-xl font-bold gap-1" wire:loading.attr="disabled" wire:target="deleteStall"><span wire:loading wire:target="deleteStall" class="loading loading-spinner loading-xs"></span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Delete Stall
                    </button>
                    <div class="flex gap-2">
                        <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                        <button wire:click="openModal({{ $selectedStall->id }})" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            Edit Stall
                        </button>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Stall Add/Edit Form Modal -->
    @if($showModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-lg font-bold text-base-content">{{ $stallId ? 'Edit Stall' : 'Add Stall' }}</h3>
                <form wire:submit.prevent="saveStall" class="space-y-3.5">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Stall Photo</span></label>
                        <input type="file" wire:model="image" accept="image/*"
                            class="file-input file-input-bordered file-input-sm rounded-xl text-xs w-full">
                        @error('image') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror

                        @if($image)
                            <div class="mt-2 flex items-center gap-2">
                                <img src="{{ $image->temporaryUrl() }}"
                                    class="w-12 h-12 rounded-xl object-cover border border-base-300">
                                <span class="text-xs text-success font-bold">New image selected</span>
                            </div>
                        @elseif($existing_image)
                            <div class="mt-2 flex items-center gap-2">
                                <img src="{{ asset('storage/' . $existing_image) }}"
                                    class="w-12 h-12 rounded-xl object-cover border border-base-300">
                                <span class="text-xs text-base-content/60">Current storefront photo</span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Stall Number *</span></label>
                            <input type="text" wire:model="stall_number" placeholder="e.g. GF-001" required
                                class="input input-bordered input-sm rounded-xl text-sm">
                            @error('stall_number') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Floor Location</span></label>
                            <select wire:model="location_id" class="select select-bordered select-sm rounded-xl text-sm">
                                <option value="">-- No Floor Assigned --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Monthly Price (₱) *</span></label>
                            <input type="number" step="0.01" wire:model="price" placeholder="0.00" required
                                class="input input-bordered input-sm rounded-xl text-sm">
                            @error('price') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Status *</span></label>
                            <select wire:model="status" class="select select-bordered select-sm rounded-xl text-sm">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            @error('status') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-1"><span class="label-text font-bold text-xs uppercase">Description</span></label>
                        <textarea wire:model="description" rows="2" placeholder="Stall specifications, features..."
                            class="textarea textarea-bordered rounded-xl text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl font-bold" wire:loading.attr="disabled" wire:target="saveStall"><span wire:loading wire:target="saveStall" class="loading loading-spinner loading-xs"></span>Save Stall</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
        </dialog>
    @endif
</div>