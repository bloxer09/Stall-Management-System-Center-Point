<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">My Leases</h1>
            <p class="text-xs text-base-content/60 mt-1">Review your assigned commercial stalls, contract periods, and good standing status.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
        <div class="w-full sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search stall number..." 
                class="input input-bordered input-sm rounded-xl text-xs w-full focus:input-primary" />
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select wire:model.live="statusFilter" class="select select-bordered select-sm rounded-xl text-xs w-full sm:w-auto">
                <option value="">All Statuses</option>
                <option value="active">Good Standing (Active)</option>
                <option value="pending">Pending Initial Payment</option>
                <option value="inactive">Inactive / Concluded</option>
            </select>
        </div>
    </div>

    <!-- Leases Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-xs">
                <thead class="bg-base-200/50 text-base-content/70 uppercase font-black text-[10px] tracking-wider">
                    <tr>
                        <th>Stall Space</th>
                        <th>Floor Location</th>
                        <th>Monthly Rent</th>
                        <th>Contract Active Dates</th>
                        <th>Standing / Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($leases as $lease)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-bold text-base-content">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $lease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                                        class="w-10 h-10 rounded-xl object-cover border border-base-300 cursor-pointer flex-shrink-0"
                                        alt="Stall" title="Click to view full photo">
                                    <div>
                                        <div class="font-extrabold text-sm text-base-content">Stall #{{ $lease->stall->stall_number }}</div>
                                        <div class="text-[11px] text-base-content/50">{{ $lease->stall->description ? Str::limit($lease->stall->description, 28) : 'Commercial Unit' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-semibold text-base-content/80">
                                {{ $lease->stall->location->name ?? 'Main Commercial Floor' }}
                            </td>
                            <td class="font-mono font-bold text-primary text-sm">
                                ₱{{ number_format($lease->stall->price, 2) }}
                            </td>
                            <td class="font-mono text-base-content/80">
                                {{ $lease->start_date->format('M d, Y') }} &rarr; {{ $lease->end_date->format('M d, Y') }}
                            </td>
                            <td>
                                @if($lease->payment_status === 'pending_initial')
                                    <span class="badge badge-warning text-slate-900 font-extrabold text-[10px] uppercase gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 animate-pulse"></span>
                                        Pending Initial Payment
                                    </span>
                                @elseif($lease->is_active)
                                    <span class="badge badge-success text-white font-extrabold text-[10px] uppercase gap-1">
                                        <span>✓</span> Good Standing
                                    </span>
                                @else
                                    <span class="badge badge-ghost text-base-content/60 font-bold text-[10px] uppercase">
                                        Concluded
                                    </span>
                                @endif

                                @if($lease->is_active && $lease->renewal_status === 'requested')
                                    <div class="text-[11px] font-bold text-warning mt-1">Renewal requested — awaiting review</div>
                                    <div class="text-[10px] text-base-content/60">Until {{ $lease->requested_end_date?->format('M d, Y') }}</div>
                                @elseif($lease->is_active && $lease->renewal_status === 'rejected')
                                    <div class="text-[11px] font-bold text-error mt-1">Renewal not approved</div>
                                    <div class="text-[10px] text-base-content/60 max-w-[220px] whitespace-normal">{{ $lease->renewal_rejection_reason }}</div>
                                @elseif($lease->is_active && $lease->renewal_status === 'approved')
                                    <div class="text-[11px] font-bold text-success mt-1">Renewed until {{ $lease->end_date->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($lease->payment_status === 'pending_initial')
                                        <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-warning btn-xs rounded-xl font-black text-slate-900 shadow-xs">
                                            Pay to Activate
                                        </a>
                                    @endif
                                    @if(in_array($lease->id, $renewableIds, true))
                                        <button wire:click="openRenewal({{ $lease->id }})" class="btn btn-primary btn-xs rounded-xl font-bold">
                                            Request renewal
                                        </button>
                                    @endif
                                    <button wire:click="openDetailModal({{ $lease->id }})" class="btn btn-ghost btn-xs text-primary font-bold rounded-xl">
                                        View Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-base-content/50">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <p class="font-bold text-sm">No commercial stall leases found.</p>
                                    <p class="text-xs text-base-content/40">Contact property management to assign or lease a retail stall unit.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leases->hasPages())
            <div class="p-4 border-t border-base-200">
                {{ $leases->links() }}
            </div>
        @endif
    </div>

    <!-- Modal 1: Lease Detail Modal -->
    @if($showDetailModal && $selectedLease)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeDetailModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Header Info -->
                <div class="flex items-start gap-4">
                    <img src="{{ $selectedLease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                        class="w-16 h-16 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer"
                        alt="Stall" title="Click to view full photo">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary">Commercial Unit</span>
                        <h3 class="font-black text-xl text-base-content">Stall #{{ $selectedLease->stall->stall_number }}</h3>
                        <p class="text-xs font-semibold text-base-content/60">{{ $selectedLease->stall->location->name ?? 'Commercial Building' }}</p>
                    </div>
                </div>

                <!-- Status Banner -->
                @if($selectedLease->payment_status === 'pending_initial')
                    <div class="p-4 bg-warning/15 border border-warning/30 rounded-2xl text-xs space-y-2">
                        <div class="font-black text-warning-content flex items-center gap-1.5 uppercase tracking-wide text-[11px]">
                            <span class="w-2 h-2 rounded-full bg-warning animate-ping"></span>
                            Action Required: Initial Payment Pending
                        </div>
                        <p class="text-base-content/80 leading-relaxed">
                            This stall is currently reserved for your account. Once your first monthly rental payment is settled, the lease agreement activates immediately and you may occupy the stall.
                        </p>
                        <div class="pt-1">
                            <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-warning btn-sm rounded-xl font-black text-slate-900 shadow-xs w-full">
                                Go to Checkout &amp; Pay via PayMongo / Cash
                            </a>
                        </div>
                    </div>
                @elseif($selectedLease->is_active)
                    <div class="p-3.5 bg-success/10 border border-success/30 rounded-2xl text-xs space-y-1">
                        <div class="font-black text-success flex items-center gap-1.5 uppercase tracking-wide text-[11px]">
                            <span>✓</span> Active &bull; In Good Standing
                        </div>
                        <p class="text-base-content/80 text-[11px]">
                            Your lease stays continuously active and renewed as you settle your monthly invoices on time.
                        </p>
                    </div>
                @endif

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-3 p-4 bg-base-200/40 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Monthly Rental Rate</span>
                        <span class="font-mono font-black text-primary text-sm">₱{{ number_format($selectedLease->stall->price, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Lease Validity</span>
                        <span class="font-mono font-semibold text-base-content">{{ $selectedLease->start_date->format('M d') }} - {{ $selectedLease->end_date->format('M d, Y') }}</span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-base-300/50">
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Stall Details</span>
                        <p class="text-base-content/80 mt-0.5">{{ $selectedLease->stall->description ?: 'Standard retail stall space equipped for commercial enterprise operations.' }}</p>
                    </div>
                </div>

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
                                Change Store Photo
                            </button>
                        @endif
                    </div>
                    <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    @include('livewire.partials.renewal-modal')

    <!-- Modal 2: Stall Photo Upload Modal -->
    @if($showImageModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeImageModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeImageModal()">
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
</div>