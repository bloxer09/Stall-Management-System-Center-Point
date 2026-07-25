<div class="space-y-6">
    @if(session()->has('message'))
    <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('message') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">Tenant Lease Allocations</h2>
            <p class="text-xs text-base-content/60 mt-1">Assign tenants to stalls, upload lease contracts, and review extension requests.</p>
        </div>
        <button wire:click="openModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Assign Lease
        </button>
    </div>

    <!-- Mappings Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 overflow-hidden shadow-md">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tenant or stall number..." class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Lease Statuses</option>
                    <option value="active">Active Leases</option>
                    <option value="requested">Requested Renewal</option>
                    <option value="terminated">Terminated</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-left text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Stall Number</th>
                        <th class="font-bold">Lease Period</th>
                        <th class="font-bold">Renewal Status</th>
                        <th class="font-bold">Contract PDF</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($mappings as $map)
                    <tr class="hover:bg-base-200/40 transition">
                        <td class="font-bold text-base-content">{{ $map->user->name }}</td>
                        <td class="text-base-content/80">Stall {{ $map->stall->stall_number }}</td>
                        <td class="text-xs text-base-content/60 font-mono">
                            {{ $map->start_date->format('M d, Y') }} - {{ $map->end_date->format('M d, Y') }}
                        </td>
                        <td>
                            @if($map->renewal_status === 'requested')
                            <span class="badge badge-warning font-bold text-xs">
                                Requested to {{ $map->requested_end_date ? $map->requested_end_date->format('M d, Y') : '' }}
                            </span>
                            @else
                            <span class="badge badge-neutral font-semibold text-xs uppercase">
                                {{ $map->renewal_status }}
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($map->contract_document)
                            <a href="{{ route('file.contract', $map->id) }}" target="_blank" class="link link-primary font-bold text-xs flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> Download
                            </a>
                            @else
                            <span class="text-xs text-base-content/40">None</span>
                            @endif
                        </td>
                        <td class="text-right space-x-1">
                            @if($map->renewal_status === 'requested')
                            <button wire:click="approveRenewal({{ $map->id }})" class="btn btn-ghost btn-xs text-success font-bold">Approve</button>
                            <button wire:click="rejectRenewal({{ $map->id }})" class="btn btn-ghost btn-xs text-error font-bold">Reject</button>
                            @endif
                            @if($map->is_active)
                            <button wire:click="terminateLease({{ $map->id }})" wire:confirm="End this lease mapping?" class="btn btn-ghost btn-xs text-warning font-bold">Terminate</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="p-8 text-center text-base-content/40 text-xs font-semibold">No active leases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">{{ $mappings->links() }}</div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-base-content">Assign Stall Lease</h3>
            
            <div class="space-y-3 text-sm">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Tenant User</span></label>
                    <select wire:model="user_id" class="select select-bordered rounded-xl text-sm">
                        <option value="">Select User</option>
                        @foreach($availableUsers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Stall</span></label>
                    <select wire:model="stall_id" class="select select-bordered rounded-xl text-sm">
                        <option value="">Select Available Stall</option>
                        @foreach($availableStalls as $st)
                        <option value="{{ $st->id }}">Stall {{ $st->stall_number }} (₱{{ number_format($st->price) }}/mo)</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Start Date</span></label>
                        <input type="date" wire:model="start_date" class="input input-bordered rounded-xl text-sm">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">End Date</span></label>
                        <input type="date" wire:model="end_date" class="input input-bordered rounded-xl text-sm">
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Lease Contract PDF (Optional)</span></label>
                    <input type="file" wire:model="contract_file" class="file-input file-input-bordered file-input-sm rounded-xl text-xs">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                <button wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                <button wire:click="saveMapping" class="btn btn-primary rounded-xl font-bold">Create Lease</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
    </dialog>
    @endif
</div>

