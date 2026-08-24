<div class="space-y-6">
    @if(session()->has('message'))
    <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('message') }}</span>
    </div>
    @endif

    <div class="card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">My Active Lease Allocations</h2>
            <p class="text-xs text-base-content/60 mt-1">View active contract details, download signed documents, and request lease extensions.</p>
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
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search stall number or location..." class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Statuses</option>
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
                        <th class="font-bold">Stall</th>
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
                        <td class="font-extrabold text-base-content">Stall #{{ $lease->stall->stall_number }}</td>
                        <td>
                            <span class="badge badge-primary font-bold text-xs">{{ $lease->stall->location->name ?? 'Main Floor' }}</span>
                        </td>
                        <td class="text-xs font-mono text-base-content/80 font-medium">
                            {{ $lease->start_date->format('M d, Y') }} - {{ $lease->end_date->format('M d, Y') }}
                        </td>
                        <td class="font-bold text-primary">₱{{ number_format($lease->stall->price, 2) }}</td>
                        <td>
                            @if($lease->contract_document)
                            <a href="{{ route('file.contract', $lease->id) }}" target="_blank" class="text-xs font-bold link link-primary flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> Contract PDF
                            </a>
                            @else
                            <span class="text-xs text-base-content/40 font-medium">None</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($lease->is_active && $lease->renewal_status !== 'requested')
                            <button wire:click="openRenewalModal({{ $lease->id }})" class="btn btn-primary btn-sm rounded-xl font-bold">
                                Request Renewal
                            </button>
                            @elseif($lease->renewal_status === 'requested')
                            <span class="badge badge-warning font-bold text-xs">Renewal Requested</span>
                            @else
                            <span class="badge badge-neutral font-bold text-xs">Terminated</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center text-xs font-semibold text-base-content/50 space-y-3">
                            <div>No active lease contracts found matching criteria.</div>
                            <div>
                                <a href="{{ route('home') }}" class="btn btn-primary btn-sm rounded-xl font-bold">Browse Available Stalls</a>
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

    <!-- Renewal Modal -->
    @if($showRenewalModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 space-y-4 shadow-2xl">
            <h3 class="text-lg font-bold text-base-content">Request Lease Extension</h3>
            
            <div class="space-y-3 text-sm">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Requested End Date</span></label>
                    <input type="date" wire:model="requested_end_date" class="input input-bordered rounded-xl text-sm">
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-base-200">
                <button wire:click="closeRenewalModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                <button wire:click="submitRenewalRequest" class="btn btn-primary rounded-xl font-bold">Submit Request</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeRenewalModal">close</button></form>
    </dialog>
    @endif
</div>
