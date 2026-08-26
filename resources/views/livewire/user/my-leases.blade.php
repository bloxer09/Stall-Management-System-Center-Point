<div class="space-y-6">

    <div
        class="card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">My Active Lease Allocations</h2>
            <p class="text-xs text-base-content/60 mt-1">View active contract details, upload stall cover photos,
                download signed documents, and request lease extensions.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="stat bg-base-200/60 py-2 px-4 rounded-2xl border border-base-300">
                <div class="stat-title text-[10px] font-extrabold uppercase text-base-content/60">Active Leases</div>
                <div class="stat-value text-lg font-black text-primary">{{ $activeCount }}</div>
            </div>
        </div>
    </div>

    <!-- Leases Data Table Container -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <!-- Search & Filter Bar -->
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search stall number or location..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All</option>
                    <option value="active">Active Leases</option>
                    <option value="requested">Requested Renewal</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Stall & Cover Photo</th>
                        <th class="font-bold">Floor Level</th>
                        <th class="font-bold">Lease Period</th>
                        <th class="font-bold">Monthly Price</th>
                        <th class="font-bold">Contract PDF</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($leases as $lease)
                        <tr class="hover:bg-base-200/40 transition">
                            <td>
                                <div class="flex items-center gap-3">
                                    @if($lease->stall->image)
                                        <img src="{{ asset('storage/' . $lease->stall->image) }}"
                                            onclick="openImageLightbox(this.src)"
                                            class="w-12 h-12 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer hover:scale-110 transition"
                                            alt="Stall Photo" title="Click to view full photo">
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
                                        <div class="font-extrabold text-base-content">Stall
                                            #{{ $lease->stall->stall_number }}</div>
                                        @if($lease->is_active)
                                            <button wire:click="openImageModal({{ $lease->stall_id }})"
                                                class="btn btn-ghost btn-xs text-primary font-bold px-0 underline">
                                                Change Photo
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge badge-primary font-bold text-xs">{{ $lease->stall->location->name ?? 'Main Floor' }}</span>
                            </td>
                            <td class="text-xs font-mono text-base-content/80 font-medium">
                                {{ $lease->start_date->format('M d, Y') }} - {{ $lease->end_date->format('M d, Y') }}
                            </td>
                            <td class="font-bold text-primary">₱{{ number_format($lease->stall->price, 2) }}</td>
                            <td>
                                @if($lease->contract_document)
                                    <a href="{{ route('file.contract', $lease->id) }}" target="_blank"
                                        class="text-xs font-bold link link-primary flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z" />
                                        </svg> Contract PDF
                                    </a>
                                @else
                                    <span class="text-xs text-base-content/40 font-medium">None</span>
                                @endif
                            </td>
                            <td class="text-right space-x-1">
                                @if($lease->is_active)
                                    <button wire:click="openImageModal({{ $lease->stall_id }})"
                                        class="btn btn-outline btn-xs rounded-xl font-bold">
                                        Change Photo
                                    </button>
                                    @if($lease->renewal_status !== 'requested')
                                        <button wire:click="openRenewalModal({{ $lease->id }})"
                                            class="btn btn-primary btn-xs rounded-xl font-bold">
                                            Request Renewal
                                        </button>
                                    @else
                                        <span class="badge badge-warning font-bold text-xs">Renewal Requested</span>
                                    @endif
                                @else
                                    <span class="badge badge-neutral font-bold text-xs">Terminated</span>
                                @endif
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
                                        <p class="text-xs mt-0.5">Your active stall lease allocations will appear here once
                                            assigned.</p>
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

    <!-- Renewal Extension Modal -->
    @if($showRenewalModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
                <h3 class="text-lg font-bold text-base-content">Request Lease Extension</h3>

                <div class="space-y-3 text-sm">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Requested End
                                Date</span></label>
                        <input type="date" wire:model="requested_end_date" class="input input-bordered rounded-xl text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                    <button wire:click="closeRenewalModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button wire:click="submitRenewalRequest" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        wire:target="submitRenewalRequest" class="btn btn-primary rounded-xl font-bold gap-2">
                        <span wire:loading wire:target="submitRenewalRequest"
                            class="loading loading-spinner loading-xs"></span>
                        Submit Request
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeRenewalModal">close</button></form>
        </dialog>
    @endif

    <!-- Change Stall Photo Modal -->
    @if($showImageModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
                <h3 class="text-lg font-bold text-base-content">Update Stall Cover Photo</h3>
                <form wire:submit.prevent="uploadStallPhoto" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Select Image File (Max
                                5MB)</span></label>
                        <input type="file" wire:model="newStallImage" accept="image/*"
                            class="file-input file-input-bordered file-input-sm rounded-xl text-xs">
                        @error('newStallImage') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror

                        <div class="mt-3">
                            @if($newStallImage)
                                <div class="flex items-center gap-3 bg-base-200/60 p-2.5 rounded-2xl border border-base-300">
                                    <img src="{{ $newStallImage->temporaryUrl() }}"
                                        class="w-16 h-16 rounded-xl object-cover border border-base-300">
                                    <div>
                                        <span class="text-xs text-success font-bold block">New Photo Selected</span>
                                        <span
                                            class="text-[10px] text-base-content/60 block truncate max-w-[180px]">{{ $newStallImage->getClientOriginalName() }}</span>
                                    </div>
                                </div>
                            @elseif($existingStallImage)
                                <div class="flex items-center gap-3 bg-base-200/60 p-2.5 rounded-2xl border border-base-300">
                                    <img src="{{ asset('storage/' . $existingStallImage) }}"
                                        class="w-16 h-16 rounded-xl object-cover border border-base-300">
                                    <span class="text-xs text-base-content/60 font-medium">Current Cover Photo</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                        <button type="button" wire:click="closeImageModal"
                            class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-60"
                            class="btn btn-primary rounded-xl font-bold gap-2">
                            <span wire:loading class="loading loading-spinner loading-xs"></span>
                            Upload Photo
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeImageModal">close</button></form>
        </dialog>
    @endif
</div>