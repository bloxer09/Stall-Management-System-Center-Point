<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Leases & Tenants</h1>
            <p class="text-xs text-base-content/60 mt-1">Assign, track, renew, and manage commercial stall leases.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Assign Lease
        </button>
    </div>

    <!-- Leases Table -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search tenant or stall..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm w-full md:w-auto">
                <option value="">All Lease Statuses</option>
                <option value="active">Active Leases</option>
                <option value="requested">Renewal Requested</option>
                <option value="inactive">Inactive / Expired</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Stall</th>
                        <th class="font-bold">Lease Period</th>
                        <th class="font-bold">Status</th>
                        <th class="font-bold">Contract</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($mappings as $map)
                    <tr class="hover:bg-base-200/40 transition">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full overflow-hidden border border-primary/20 shadow-xs shrink-0">
                                    <img src="{{ $map->user->avatar_url }}" onclick="openImageLightbox(this.src)" class="w-full h-full object-cover cursor-pointer" alt="Tenant Avatar" title="Click to view photo">
                                </div>
                                <div>
                                    <div class="font-extrabold text-sm text-base-content">{{ $map->user->name }}</div>
                                    <div class="text-[11px] text-base-content/60">{{ $map->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-base-content font-bold">
                            Stall #{{ $map->stall->stall_number }}
                            <span class="block text-[11px] text-base-content/60 font-normal">{{ $map->stall->location->name ?? 'Main Floor' }}</span>
                        </td>
                        <td class="text-xs text-base-content/80 font-mono">
                            {{ $map->start_date->format('M d, Y') }} - {{ $map->end_date->format('M d, Y') }}
                        </td>
                        <td>
                            @if($map->renewal_status === 'requested')
                                <span class="badge badge-warning font-bold text-xs">
                                    Renewal Requested
                                </span>
                            @elseif($map->is_active)
                                <span class="badge badge-success text-white font-bold text-xs">
                                    Active
                                </span>
                            @else
                                <span class="badge badge-neutral font-semibold text-xs uppercase">
                                    Ended
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($map->contract_document)
                                <a href="{{ route('file.contract', $map->id) }}" target="_blank" class="link link-primary font-bold text-xs flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> PDF
                                </a>
                            @else
                                <span class="text-xs text-base-content/40 font-medium">None</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <button wire:click="openDetailModal({{ $map->id }})" class="btn btn-ghost btn-xs text-primary font-bold rounded-lg border border-primary/20 hover:bg-primary hover:text-white transition">
                                Manage
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-base-content/40">
                                <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <div>
                                    <p class="font-extrabold text-sm">No leases found</p>
                                    <p class="text-xs mt-0.5">Try adjusting your filters or assign a new tenant lease.</p>
                                </div>
                                <button wire:click="openModal" class="btn btn-primary btn-xs rounded-xl font-bold mt-1">+ Assign First Lease</button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">{{ $mappings->links() }}</div>
    </div>

    <!-- Modal 1: Lease Detail & Management Actions Modal -->
    @if($showDetailModal && $selectedLease)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full overflow-hidden border border-primary/20 shadow-xs shrink-0">
                        <img src="{{ $selectedLease->user->avatar_url }}" class="w-full h-full object-cover" alt="Tenant Avatar">
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-base-content">{{ $selectedLease->user->name }}</h3>
                        <p class="text-xs text-base-content/60">{{ $selectedLease->user->email }} &bull; {{ $selectedLease->user->phone ?? 'No phone' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 bg-base-200/60 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Assigned Stall</span>
                        <span class="font-extrabold text-sm text-base-content mt-0.5 block">Stall #{{ $selectedLease->stall->stall_number }}</span>
                        <span class="text-[11px] text-base-content/60">{{ $selectedLease->stall->location->name ?? 'Main Floor' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Monthly Rate</span>
                        <span class="font-extrabold text-sm text-primary mt-0.5 block">₱{{ number_format($selectedLease->stall->price, 2) }}</span>
                        <span class="text-[11px] text-base-content/60">Per Month</span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-base-300">
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Contract Term</span>
                        <span class="font-mono text-xs font-bold text-base-content mt-0.5 block">
                            {{ $selectedLease->start_date->format('F d, Y') }} &mdash; {{ $selectedLease->end_date->format('F d, Y') }}
                        </span>
                    </div>
                </div>

                @if($selectedLease->renewal_status === 'requested')
                    <div class="p-3 bg-warning/10 border border-warning/30 rounded-2xl text-xs space-y-1">
                        <div class="font-bold text-warning-content flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            Renewal Extension Requested
                        </div>
                        <p class="text-base-content/80">
                            The tenant has requested to extend their lease until <strong>{{ $selectedLease->requested_end_date ? $selectedLease->requested_end_date->format('F d, Y') : 'N/A' }}</strong>.
                        </p>
                    </div>
                @endif

                @if($selectedLease->contract_document)
                    <div class="flex items-center justify-between p-3 bg-base-200/30 rounded-2xl border border-base-300/50">
                        <div class="flex items-center gap-2 text-xs font-bold text-base-content">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Lease Contract Document
                        </div>
                        <a href="{{ route('file.contract', $selectedLease->id) }}" target="_blank" class="btn btn-xs btn-primary btn-outline rounded-xl font-bold gap-1">
                            Download PDF
                        </a>
                    </div>
                @endif

                <!-- Modal Actions -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-base-200">
                    <div>
                        @if($selectedLease->is_active)
                            <button wire:click="terminateLease({{ $selectedLease->id }})" wire:confirm="Are you sure you want to terminate this lease contract?" class="btn btn-error btn-outline btn-sm rounded-xl font-bold">
                                Terminate Lease
                            </button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($selectedLease->renewal_status === 'requested')
                            <button wire:click="rejectRenewal({{ $selectedLease->id }})" class="btn btn-error btn-sm rounded-xl font-bold">Reject</button>
                            <button wire:click="approveRenewal({{ $selectedLease->id }})" class="btn btn-success text-white btn-sm rounded-xl font-bold">Approve Renewal</button>
                        @endif
                        <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                    </div>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Assign Lease Modal -->
    @if($showModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-base-content">Assign Lease Contract</h3>
            <form wire:submit.prevent="saveMapping" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Select Tenant</span></label>
                    <select wire:model="user_id" required class="select select-bordered rounded-xl text-sm">
                        <option value="">-- Choose Tenant Account --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Select Stall</span></label>
                    <select wire:model="stall_id" required class="select select-bordered rounded-xl text-sm">
                        <option value="">-- Choose Available Stall --</option>
                        @foreach($availableStalls as $st)
                            <option value="{{ $st->id }}">Stall #{{ $st->stall_number }} - {{ $st->location->name ?? 'Main' }} (₱{{ number_format($st->price, 2) }}/mo)</option>
                        @endforeach
                    </select>
                    @error('stall_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Start Date</span></label>
                        <input type="date" wire:model="start_date" required class="input input-bordered rounded-xl text-sm">
                        @error('start_date') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">End Date</span></label>
                        <input type="date" wire:model="end_date" required class="input input-bordered rounded-xl text-sm">
                        @error('end_date') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Contract PDF (Optional)</span></label>
                    <input type="file" wire:model="contract_file" accept=".pdf,image/*" class="file-input file-input-bordered file-input-sm rounded-xl text-xs">
                    @error('contract_file') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-xl font-bold">Assign Lease</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
    </dialog>
    @endif
</div>
