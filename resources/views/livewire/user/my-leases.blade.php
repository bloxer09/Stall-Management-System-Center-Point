<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">My Leases</h1>
            <p class="text-xs text-base-content/60 mt-1">Review your commercial stall lease agreements and renewals.</p>
        </div>
    </div>

    <!-- Leases Table Card -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
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
            <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm w-full md:w-auto">
                <option value="">All Leases</option>
                <option value="active">Active Leases</option>
                <option value="requested">Renewal Requested</option>
                <option value="inactive">Terminated / Expired</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Stall</th>
                        <th class="font-bold">Floor Level</th>
                        <th class="font-bold">Lease Term</th>
                        <th class="font-bold">Monthly Rate</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($leases as $lease)
                        <tr class="hover:bg-base-200/40 transition">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if($lease->stall->image)
                                        <img src="{{ $lease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                                            class="w-12 h-12 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-105 transition"
                                            alt="Stall Photo" title="Click to view photo">
                                    @else
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-base-200 border border-base-300 flex items-center justify-center text-base-content/40">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-extrabold text-base-content">Stall #{{ $lease->stall->stall_number }}</div>
                                        <span class="text-xs text-base-content/60">{{ $lease->stall->location->name ?? 'Main Floor' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary font-bold text-xs">{{ $lease->stall->location->name ?? 'Main Floor' }}</span>
                            </td>
                            <td class="text-xs font-mono text-base-content/80 font-medium">
                                {{ $lease->start_date->format('M d, Y') }} - {{ $lease->end_date->format('M d, Y') }}
                            </td>
                            <td class="font-bold text-primary">₱{{ number_format($lease->stall->price, 2) }}</td>
                            <td>
                                @if($lease->renewal_status === 'requested')
                                    <span class="badge badge-warning font-bold text-xs">Renewal Requested</span>
                                @elseif($lease->is_active)
                                    <span class="badge badge-success text-white font-bold text-xs">Active</span>
                                @else
                                    <span class="badge badge-neutral font-bold text-xs">Ended</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button wire:click="openDetailModal({{ $lease->id }})"
                                    class="btn btn-ghost btn-xs text-primary font-bold rounded-lg border border-primary/20 hover:bg-primary hover:text-white transition">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No lease contracts found</p>
                                        <p class="text-xs mt-0.5">Your active stall lease allocations will appear here once assigned.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $leases->links() }}
        </div>
    </div>

    <!-- Modal 1: Lease Detail & Actions Modal -->
    @if($showDetailModal && $selectedLease)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex items-start gap-4">
                    <img src="{{ $selectedLease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                        class="w-20 h-20 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-105 transition"
                        alt="Stall Photo" title="Click to view full photo">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-black text-base-content">Stall #{{ $selectedLease->stall->stall_number }}</h3>
                            <span class="badge badge-sm font-bold uppercase {{ $selectedLease->is_active ? 'badge-success text-white' : 'badge-neutral' }}">
                                {{ $selectedLease->is_active ? 'Active' : 'Ended' }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-primary">
                            {{ $selectedLease->stall->location->name ?? 'Main Floor' }} &bull; ₱{{ number_format($selectedLease->stall->price, 2) }} / month
                        </p>
                        <p class="text-xs text-base-content/60 font-mono">
                            Term: {{ $selectedLease->start_date->format('M d, Y') }} &mdash; {{ $selectedLease->end_date->format('M d, Y') }}
                        </p>
                    </div>
                </div>

                @if($selectedLease->renewal_status === 'requested')
                    <div class="p-3 bg-warning/10 border border-warning/30 rounded-2xl text-xs space-y-1">
                        <div class="font-bold text-warning-content flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Renewal Extension Pending
                        </div>
                        <p class="text-base-content/80">
                            Your renewal request until <strong>{{ $selectedLease->requested_end_date ? $selectedLease->requested_end_date->format('F d, Y') : 'N/A' }}</strong> is currently under review by management.
                        </p>
                    </div>
                @endif

                @if($selectedLease->contract_document)
                    <div class="flex items-center justify-between p-3 bg-base-200/40 rounded-2xl border border-base-300">
                        <div class="flex items-center gap-2 text-xs font-bold text-base-content">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Official Lease Agreement
                        </div>
                        <a href="{{ route('file.contract', $selectedLease->id) }}" target="_blank" class="btn btn-xs btn-primary btn-outline rounded-xl font-bold gap-1">
                            Download PDF
                        </a>
                    </div>
                @endif

                <!-- Modal Actions -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-base-200">
                    <div class="flex gap-2">
                        @if($selectedLease->is_active)
                            <button wire:click="openImageModal({{ $selectedLease->stall_id }})" class="btn btn-outline btn-sm rounded-xl font-bold gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                Change Photo
                            </button>
                            @if($selectedLease->renewal_status !== 'requested')
                                <button wire:click="openRenewalModal({{ $selectedLease->id }})" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Request Renewal
                                </button>
                            @endif
                        @endif
                    </div>
                    <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Stall Photo Upload Modal -->
    @if($showImageModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeImageModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="font-black text-lg text-base-content">Update Stall Photo</h3>
                <p class="text-xs text-base-content/60">Upload a fresh photo of your stall storefront or business setup.</p>

                <form wire:submit.prevent="uploadStallPhoto" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Choose Image</span></label>
                        <input type="file" wire:model="newStallImage" accept="image/*" required
                            class="file-input file-input-bordered file-input-sm rounded-xl text-xs">
                        @error('newStallImage') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror

                        @if($newStallImage)
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ $newStallImage->temporaryUrl() }}" class="w-16 h-16 rounded-xl object-cover border border-base-300 shadow-xs">
                                <span class="text-xs text-success font-bold">Ready to upload</span>
                            </div>
                        @elseif($existingStallImage)
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ asset('storage/' . $existingStallImage) }}" class="w-16 h-16 rounded-xl object-cover border border-base-300">
                                <span class="text-xs text-base-content/60">Current storefront photo</span>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="closeImageModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold">Upload Photo</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeImageModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 3: Lease Extension / Renewal Modal -->
    @if($showRenewalModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeRenewalModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="font-black text-lg text-base-content">Request Lease Extension</h3>
                <p class="text-xs text-base-content/60">Submit a formal renewal request to the building administrator.</p>

                <form wire:submit.prevent="submitRenewalRequest" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Requested New End Date</span></label>
                        <input type="date" wire:model="requested_end_date" required class="input input-bordered rounded-xl text-sm">
                        @error('requested_end_date') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="closeRenewalModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold">Submit Renewal Request</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeRenewalModal">close</button></form>
        </dialog>
    @endif
</div>