<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Commercial Lease Contracts</h1>
            <p class="text-xs text-base-content/60 mt-1">Assign, track, and manage commercial stall lease agreements and standing.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Assign Stall to Tenant
        </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
        <div class="w-full sm:w-80">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tenant or stall..." 
                class="input input-bordered input-sm rounded-xl text-xs w-full focus:input-primary" />
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select wire:model.live="statusFilter" class="select select-bordered select-sm rounded-xl text-xs w-full sm:w-auto">
                <option value="">All Statuses</option>
                <option value="active">Good Standing (Active)</option>
                <option value="pending">Pending Initial Payment</option>
                <option value="inactive">Inactive / Terminated</option>
            </select>
        </div>
    </div>

    <!-- Leases Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-xs">
                <thead class="bg-base-200/50 text-base-content/70 uppercase font-black text-[10px] tracking-wider">
                    <tr>
                        <th>Tenant</th>
                        <th>Stall Space</th>
                        <th>Monthly Rate</th>
                        <th>Contract Active Dates</th>
                        <th>Standing / Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($mappings as $map)
                        <tr class="hover:bg-base-200/40 transition">
                            <td>
                                <div class="font-bold text-base-content">{{ $map->user->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-base-content/60">{{ $map->user->email ?? 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="font-extrabold text-sm text-base-content">Stall #{{ $map->stall->stall_number ?? 'N/A' }}</div>
                                <div class="text-[11px] text-base-content/50">{{ $map->stall->location->name ?? 'Commercial Building' }}</div>
                            </td>
                            <td class="font-mono font-bold text-primary text-sm">
                                ₱{{ number_format($map->stall->price ?? 0, 2) }}
                            </td>
                            <td class="font-mono text-base-content/80">
                                {{ $map->start_date->format('M d, Y') }} &rarr; {{ $map->end_date->format('M d, Y') }}
                            </td>
                            <td>
                                @if($map->payment_status === 'pending_initial')
                                    <span class="badge badge-warning text-slate-900 font-extrabold text-[10px] uppercase gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 animate-pulse"></span>
                                        Pending Initial Payment
                                    </span>
                                @elseif($map->is_active)
                                    <span class="badge badge-success text-white font-extrabold text-[10px] uppercase gap-1">
                                        <span>✓</span> Good Standing
                                    </span>
                                @else
                                    <span class="badge badge-ghost text-base-content/60 font-bold text-[10px] uppercase">
                                        Terminated
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button wire:click="openDetailModal({{ $map->id }})" class="btn btn-ghost btn-xs text-primary font-bold rounded-xl">
                                    Manage
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-base-content/50">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-8 h-8 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <p class="font-bold text-sm">No commercial lease agreements found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mappings->hasPages())
            <div class="p-4 border-t border-base-200">
                {{ $mappings->links() }}
            </div>
        @endif
    </div>

    <!-- Modal 1: Assign Stall Modal -->
    @if($showModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-4 shadow-2xl">
                <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div>
                    <h3 class="font-black text-xl text-base-content">Assign Stall to Tenant</h3>
                    <p class="text-xs text-base-content/60 mt-0.5">The stall will be reserved and an initial monthly invoice will be generated for the tenant.</p>
                </div>

                <form wire:submit.prevent="saveMapping" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Select Commercial Tenant *</span></label>
                        <select wire:model="user_id" required class="select select-bordered rounded-xl text-sm w-full">
                            <option value="">-- Choose Tenant Account --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Select Available Stall *</span></label>
                        <select wire:model="stall_id" required class="select select-bordered rounded-xl text-sm w-full">
                            <option value="">-- Choose Available Stall --</option>
                            @foreach($availableStalls as $s)
                                <option value="{{ $s->id }}">Stall #{{ $s->stall_number }} &bull; {{ $s->location->name ?? 'Floor' }} (₱{{ number_format($s->price, 2) }}/mo)</option>
                            @endforeach
                        </select>
                        @error('stall_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-xs uppercase">Contract Start Date *</span></label>
                            <input type="date" wire:model="start_date" required class="input input-bordered rounded-xl text-sm w-full" />
                            @error('start_date') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label class="label"><span class="label-text font-bold text-xs uppercase">Contract End Date *</span></label>
                            <input type="date" wire:model="end_date" required class="input input-bordered rounded-xl text-sm w-full" />
                            @error('end_date') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Official Contract PDF (Optional)</span></label>
                        <input type="file" wire:model="contract_file" accept=".pdf,image/*" class="file-input file-input-bordered file-input-sm rounded-xl text-xs w-full" />
                        @error('contract_file') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="p-3 bg-base-200/50 rounded-2xl border border-base-300 text-[11px] text-base-content/70 space-y-1 font-medium">
                        <span class="font-bold text-base-content">Payment-Driven Activation Note:</span>
                        <p>Upon assignment, the stall status switches to <strong>Reserved</strong>. The lease officially activates to <strong>Occupied</strong> once the tenant pays the initial rental invoice via PayMongo or Over-the-Counter Cash.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold px-6 shadow-sm">
                            Assign Stall &amp; Generate Bill
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Lease Detail & Action Modal -->
    @if($showDetailModal && $selectedLease)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <!-- Header -->
                <div class="flex items-start gap-4">
                    <img src="{{ $selectedLease->stall->image_url }}" onclick="openImageLightbox(this.src)"
                        class="w-16 h-16 rounded-2xl object-cover border border-base-300 shadow-xs cursor-pointer"
                        alt="Stall" title="Click to view full photo">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary">Lease Record #{{ $selectedLease->id }}</span>
                        <h3 class="font-black text-xl text-base-content">Stall #{{ $selectedLease->stall->stall_number }}</h3>
                        <p class="text-xs font-bold text-base-content/70">{{ $selectedLease->user->name }} ({{ $selectedLease->user->email }})</p>
                    </div>
                </div>

                <!-- Status Banner -->
                @if($selectedLease->payment_status === 'pending_initial')
                    <div class="p-4 bg-warning/15 border border-warning/30 rounded-2xl text-xs space-y-2">
                        <div class="font-black text-warning-content flex items-center justify-between">
                            <span class="uppercase tracking-wide text-[11px] flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-warning animate-ping"></span>
                                Awaiting Initial Payment
                            </span>
                            <span class="font-mono font-black text-sm text-base-content">₱{{ number_format($selectedLease->stall->price, 2) }}</span>
                        </div>
                        <p class="text-base-content/80 text-[11px]">
                            Stall is reserved. If the tenant settled their deposit in cash at the administration office, you can confirm it below to immediately activate the lease.
                        </p>
                        <button type="button" wire:click="recordInitialCashPayment({{ $selectedLease->id }})" class="btn btn-success text-white btn-sm rounded-xl font-bold w-full shadow-xs gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Record Office Cash Payment (Activate Lease)
                        </button>
                    </div>
                @elseif($selectedLease->is_active)
                    <div class="p-3.5 bg-success/10 border border-success/30 rounded-2xl text-xs space-y-1">
                        <div class="font-black text-success flex items-center gap-1.5 uppercase tracking-wide text-[11px]">
                            <span>✓</span> Active &bull; In Good Standing
                        </div>
                        <p class="text-base-content/80 text-[11px]">
                            Lease is automatically renewed and kept active as monthly bills are settled.
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
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Contract Validity</span>
                        <span class="font-mono font-semibold text-base-content">{{ $selectedLease->start_date->format('M d, Y') }} - {{ $selectedLease->end_date->format('M d, Y') }}</span>
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
                <div class="flex items-center justify-between gap-2 pt-3 border-t border-base-200">
                    <div>
                        @if($selectedLease->is_active || $selectedLease->payment_status === 'pending_initial')
                            <button wire:click="terminateLease({{ $selectedLease->id }})" wire:confirm="Are you sure you want to terminate this lease agreement? The stall will become available." class="btn btn-outline btn-error btn-sm rounded-xl font-bold">
                                Terminate Lease
                            </button>
                        @endif
                    </div>
                    <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif
</div>
