<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Commercial Lease Contracts</h1>
            <p class="text-xs text-base-content/60 mt-1">Assign, track, and manage commercial stall lease agreements and standing.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openModal" wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-wait"
                wire:target="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Assign Stall to Tenant
            </button>
        </div>
    </div>

    <!-- Leases Table with Integrated Search & Filters -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tenant or stall number..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <option value="active">Paid</option>
                    <option value="pending">Pending Initial Payment</option>
                    <option value="inactive">Terminated</option>
                    <option value="renewal_requested">Renewal Requested</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,locationFilter,statusFilter">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Stall Space</th>
                        <th class="font-bold">Floor Level</th>
                        <th class="font-bold whitespace-nowrap">Total Contract Rent</th>
                        <th class="font-bold">Contract Term</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($mappings as $map)
                        <tr class="hover:bg-base-200/40 transition">
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full overflow-hidden border border-primary/20 shrink-0">
                                        <img src="{{ $map->user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($map->user->name ?? 'User').'&color=7F9CF5&background=EBF4FF' }}" class="w-full h-full object-cover" alt="Tenant Avatar">
                                    </div>
                                    <div>
                                        <div class="font-bold text-base-content">{{ $map->user->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-base-content/60">{{ $map->user->email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-extrabold text-base-content">
                                Stall #{{ $map->stall->stall_number ?? 'N/A' }}
                            </td>
                            <td>
                                @if($map->stall && $map->stall->location)
                                    <span class="badge badge-primary font-bold text-xs">{{ $map->stall->location->name }}</span>
                                @else
                                    <span class="text-base-content/40 text-xs font-medium">Unassigned</span>
                                @endif
                            </td>
                            <td class="font-bold text-base-content whitespace-nowrap">
                                @if($map->contract_rent !== null)
                                    ₱{{ number_format($map->contract_rent, 2) }}
                                @else
                                    <span class="font-normal text-base-content/70">Not recorded</span>
                                @endif
                                <span class="block text-xs font-normal text-base-content/70 mt-1">₱{{ number_format($map->agreed_monthly_rate ?? $map->stall->price ?? 0, 2) }} / month</span>
                            </td>
                            <td class="text-xs font-medium text-base-content/80">
                                <span class="block text-sm font-bold text-base-content mb-1">
                                    @if($map->duration_months !== null)
                                        {{ $map->duration_months }} {{ Str::plural('month', $map->duration_months) }}
                                    @else
                                        Duration not recorded
                                    @endif
                                </span>
                                {{ $map->start_date->format('M d, Y') }} &rarr; {{ $map->end_date->format('M d, Y') }}
                            </td>
                            <td>
                                @if($map->payment_status === 'pending_initial')
                                    <span class="badge badge-sm font-bold uppercase badge-warning text-slate-900 gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900 animate-pulse"></span>
                                        Pending Initial Payment
                                    </span>
                                @elseif($map->is_active)
                                    <span class="badge badge-sm font-bold uppercase badge-success text-white gap-1">
                                        <span>✓</span> Paid
                                    </span>
                                @else
                                    <span class="badge badge-sm font-bold uppercase badge-ghost text-base-content/60">
                                        Terminated
                                    </span>
                                @endif
                                @if($map->is_active && $map->renewal_status === 'awaiting_payment')
                                    <div class="text-[11px] leading-snug font-semibold text-base-content mt-1">Renewal awaiting payment</div>
                                @elseif($map->is_active && $map->renewal_status === 'requested')
                                    <span class="badge badge-sm font-bold uppercase badge-warning mt-1">Renewal requested</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-table-action wire:click="openDetailModal({{ $map->id }})">
                                    Manage
                                </x-table-action>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
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
                <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <div>
                    <h3 class="font-black text-xl text-base-content">Assign Stall to Tenant</h3>
                    <p class="text-xs text-base-content mt-0.5">Reserve a stall and invoice the full contract rent upfront.</p>
                </div>

                <form wire:submit.prevent="saveMapping" class="space-y-4">
                    <div class="form-control">
                        <label for="assignment-tenant" class="label"><span class="label-text font-bold text-xs uppercase">Select Commercial Tenant *</span></label>
                        <select id="assignment-tenant" wire:model="user_id" required class="select select-bordered rounded-xl text-sm w-full">
                            <option value="">Choose Tenant Account</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label for="assignment-stall" class="label"><span class="label-text font-bold text-xs uppercase">Select Available Stall *</span></label>
                        <select id="assignment-stall" wire:model.live="stall_id" required class="select select-bordered rounded-xl text-sm w-full">
                            <option value="">Choose Available Stall</option>
                            @foreach($availableStalls as $s)
                                <option value="{{ $s->id }}">Stall #{{ $s->stall_number }} &bull; {{ $s->location->name ?? 'Floor' }} (₱{{ number_format($s->price, 2) }}/mo)</option>
                            @endforeach
                        </select>
                        @error('stall_id') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label for="assignment-duration" class="label"><span class="label-text font-bold text-xs">Duration</span></label>
                        <select id="assignment-duration" wire:model.live="duration" class="select select-bordered rounded-xl text-sm w-full">
                            @for($months = 1; $months <= 12; $months++)
                                <option value="{{ $months }}">{{ $months }} {{ $months === 1 ? 'month' : 'months' }}</option>
                            @endfor
                        </select>
                        @error('duration') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="form-control">
                            <label for="assignment-start" class="label"><span class="label-text font-bold text-xs uppercase">Contract Start Date *</span></label>
                            <input id="assignment-start" type="date" wire:model.live="start_date" required class="input input-bordered rounded-xl text-sm w-full" />
                            @error('start_date') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-control">
                            <label for="assignment-end" class="label"><span class="label-text font-bold text-xs uppercase">Contract End Date *</span></label>
                            <input id="assignment-end" type="date" value="{{ $end_date }}" readonly class="input input-bordered rounded-xl text-sm w-full" aria-describedby="assignment-end-help" />
                            <span id="assignment-end-help" class="text-xs text-base-content mt-1">Calculated from the start date and duration.</span>
                            @error('end_date') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-base-300 p-3 text-sm text-base-content" role="status" aria-live="polite">
                        @if($assignmentRentTotal !== null)
                            <div>Monthly rate: ₱{{ number_format($assignmentMonthlyRate, 2) }}</div>
                            <div>Duration: {{ $duration }} {{ $duration === '1' ? 'month' : 'months' }}</div>
                            <div class="font-bold mt-1">Total rent due upfront: ₱{{ number_format($assignmentRentTotal, 2) }}</div>
                        @else
                            Select a stall and duration to calculate the total rent.
                        @endif
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Official Contract PDF (Optional)</span></label>
                        <input type="file" wire:model="contract_file" accept=".pdf,image/*" class="file-input file-input-bordered file-input-sm rounded-xl text-xs w-full" />
                        @error('contract_file') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="p-3 bg-base-200/50 rounded-2xl border border-base-300 text-[11px] text-base-content space-y-1 font-medium">
                        <span class="font-bold text-base-content">Payment-Driven Activation Note:</span>
                        <p>The stall is <strong>Reserved</strong> until the full contract rent is paid online or at the office. Covered rent will not be billed again each month.</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold px-6 shadow-sm" wire:loading.attr="disabled" wire:target="saveMapping"><span wire:loading wire:target="saveMapping" class="loading loading-spinner loading-xs"></span>
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
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeDetailModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <!-- Header -->
                <div class="flex items-start gap-4">
                    <img src="{{ $selectedLease->stall->image_url }}" x-data x-on:click="openImageLightbox($el.src)"
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
                            <span class="font-mono font-black text-sm text-base-content">₱{{ number_format($selectedLease->initialInvoice?->total_amount ?? $selectedLease->stall->price, 2) }}</span>
                        </div>
                        <p class="text-base-content/80 text-[11px]">
                            The stall is reserved until the rent invoice is paid. Record payment below if the tenant paid at the office.
                        </p>
                        <button type="button" wire:click="recordInitialCashPayment({{ $selectedLease->id }})" class="btn btn-success text-white btn-sm rounded-xl font-bold w-full shadow-xs gap-1.5" wire:loading.attr="disabled" wire:target="recordInitialCashPayment"><span wire:loading wire:target="recordInitialCashPayment" class="loading loading-spinner loading-xs"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Record Office Cash Payment (Activate Lease)
                        </button>
                    </div>
                @elseif($selectedLease->is_active)
                    <div class="p-3.5 bg-success/10 border border-success/30 rounded-2xl text-xs space-y-1">
                        <div class="font-black text-success flex items-center gap-1.5 uppercase tracking-wide text-[11px]">
                            <span>✓</span> Active &bull; Paid
                        </div>
                        <p class="text-base-content/80 text-[11px]">
                            {{ $selectedLease->rent_billing_mode === 'upfront' ? 'The original contract rent is billed upfront. Covered months will not be billed again.' : 'Settle monthly bills on time.' }} Renewals take effect after office review and full payment.
                        </p>
                    </div>
                @endif

                @if($selectedLease->is_active && $selectedLease->renewal_status === 'requested')
                    <div class="p-3.5 bg-warning/10 border border-warning/30 rounded-2xl text-xs space-y-3">
                        <div>
                            <div class="font-black text-warning uppercase tracking-wide text-[11px]">Renewal requested</div>
                            <p class="text-base-content/80 text-[11px] mt-1">
                                Tenant asked to extend until <strong>{{ $selectedLease->requested_end_date?->format('M d, Y') }}</strong>.
                            </p>
                            @if($selectedLease->renewal_note)
                                <p class="text-base-content/70 text-[11px] italic mt-1">“{{ $selectedLease->renewal_note }}”</p>
                            @endif
                        </div>
                        <label class="form-control w-full">
                            <span class="label-text font-bold">Reviewed renewal duration</span>
                            <select wire:model.live="renewalDurationMonths" aria-label="Reviewed renewal duration" class="select select-bordered rounded-xl min-h-11">
                                @foreach(range(1, 12) as $months)
                                    <option value="{{ $months }}">{{ $months }} {{ $months === 1 ? 'month' : 'months' }}</option>
                                @endforeach
                            </select>
                            @error('renewalDurationMonths') <p role="alert" class="text-error text-sm">{{ $message }}</p> @enderror
                            @error('renewalEndDate') <span class="text-error text-[11px] mt-1">{{ $message }}</span> @enderror
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text text-[11px] font-bold">Reason (required to reject)</span>
                            <textarea wire:model="renewalRejectReason" rows="2" maxlength="500" class="textarea textarea-bordered rounded-xl text-xs"></textarea>
                            @error('renewalRejectReason') <span class="text-error text-[11px] mt-1">{{ $message }}</span> @enderror
                        </label>
                        <p class="text-sm">Full rent: ₱{{ number_format((float) \App\Services\LeaseService::calculateContractRent((string) ($selectedLease->agreed_monthly_rate ?? $selectedLease->stall->price), max(1, min(12, $renewalDurationMonths))), 2) }}. The expiry changes only after payment.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="rejectRenewal({{ $selectedLease->id }})" class="btn btn-outline btn-error btn-sm rounded-xl font-bold flex-1">Reject</button>
                            <button type="button" wire:click="approveRenewal({{ $selectedLease->id }})" wire:loading.attr="disabled" class="btn btn-success rounded-xl font-bold flex-1 min-h-11">Confirm term and invoice</button>
                        </div>
                    </div>
                @endif

                <!-- Details Grid -->
                @if($selectedLease->renewal_status === 'awaiting_payment')
                    @include('livewire.partials.renewal-payment', ['lease' => $selectedLease])
                    <button type="button" wire:click="recordRenewalCashPayment({{ $selectedLease->id }})" wire:confirm="Confirm that the office received the full renewal invoice amount in cash." wire:loading.attr="disabled" class="btn btn-primary rounded-xl min-h-11">Record full renewal cash payment</button>
                    @error('renewalPayment') <p role="alert" class="text-error text-sm">{{ $message }}</p> @enderror
                @endif
                <div class="grid grid-cols-2 gap-3 p-4 bg-base-200/40 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Monthly Rental Rate</span>
                        <span class="font-mono font-black text-primary text-sm">₱{{ number_format($selectedLease->agreed_monthly_rate ?? $selectedLease->stall->price, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-base-content/50 block text-[10px] uppercase font-bold">Contract Validity</span>
                        <span class="font-mono font-semibold text-base-content">{{ $selectedLease->start_date->format('M d, Y') }} - {{ $selectedLease->end_date->format('M d, Y') }}</span>
                    </div>
                    @if($selectedLease->rent_billing_mode === 'upfront')
                        <div class="col-span-2">
                            <span class="text-base-content block font-bold">Contract rent · {{ $selectedLease->duration_months }} {{ Str::plural('month', $selectedLease->duration_months) }} upfront</span>
                            <span class="font-mono font-bold">₱{{ number_format($selectedLease->contract_rent, 2) }}</span>
                        </div>
                    @endif
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

                @if($selectedLease->terminated_at)
                    <div class="p-3 rounded-xl bg-error/10 border border-error/20 text-xs">
                        <div class="font-bold text-error uppercase tracking-wider">Terminated {{ $selectedLease->terminated_at->format('M d, Y') }}</div>
                        <p class="mt-1 text-base-content/80">{{ $selectedLease->termination_reason }}</p>
                    </div>
                @endif

                <!-- Modal Actions -->
                <div class="flex items-center justify-between gap-2 pt-3 border-t border-base-200">
                    <div>
                        @if(app(\App\Services\LeaseService::class)->canTerminate($selectedLease))
                            <button wire:click="openTerminate({{ $selectedLease->id }})" class="btn btn-outline btn-error btn-sm rounded-xl font-bold" wire:loading.attr="disabled" wire:target="openTerminate"><span wire:loading wire:target="openTerminate" class="loading loading-spinner loading-xs"></span>
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
    @if($terminateId)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md rounded-2xl">
                <button wire:click="closeTerminate" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeTerminate()">✕</button>
                <h3 class="font-black text-lg text-base-content">Terminate lease</h3>
                <p class="text-xs text-base-content/60 mt-1">The stall becomes available and the tenant is notified with your reason.</p>

                <form wire:submit="terminateLease" class="space-y-4 mt-4">
                    <div>
                        <label for="terminationReason" class="label text-xs font-bold">Reason</label>
                        <textarea id="terminationReason" wire:model="terminationReason" rows="3" maxlength="500" class="textarea textarea-bordered w-full rounded-xl text-sm" placeholder="e.g. Tenant closed the business at the end of the month."></textarea>
                        @error('terminationReason') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if($terminateBalance > 0)
                        <div class="p-3 rounded-xl bg-warning/15 border border-warning/30 text-xs space-y-2">
                            <div class="font-bold text-base-content">Unpaid balance: ₱{{ number_format($terminateBalance, 2) }}</div>
                            <p class="text-base-content/70">Unpaid invoices are kept and stay visible in Payments.</p>
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="acknowledgeBalance" class="checkbox checkbox-sm checkbox-warning mt-0.5">
                                <span>I understand ₱{{ number_format($terminateBalance, 2) }} stays owed by the tenant.</span>
                            </label>
                            @error('acknowledgeBalance') <p class="text-error">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <p class="text-xs text-success font-bold">No unpaid balance on this lease.</p>
                    @endif

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="closeTerminate" class="btn btn-ghost btn-sm rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-error btn-sm rounded-xl font-bold" wire:loading.attr="disabled" wire:target="terminateLease"><span wire:loading wire:target="terminateLease" class="loading loading-spinner loading-xs"></span>
                            Terminate Lease
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeTerminate">close</button></form>
        </dialog>
    @endif
</div>
