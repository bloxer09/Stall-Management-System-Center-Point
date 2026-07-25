<div class="space-y-6">
    @if(session()->has('message'))
    <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('message') }}</span>
    </div>
    @endif

    <div class="card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <h2 class="text-xl font-black tracking-tight text-base-content">My Active Lease Allocations</h2>
        <p class="text-xs text-base-content/60 mt-1">View contract details and request lease extensions.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($leases as $lease)
        <div class="card bg-base-100 rounded-3xl p-6 border border-base-300 shadow-md space-y-4">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-lg font-bold text-base-content">Stall {{ $lease->stall->stall_number }}</h3>
                    <p class="text-xs text-base-content/60">{{ $lease->stall->location->name ?? 'Main Floor' }}</p>
                </div>
                <span class="badge badge-sm font-bold uppercase {{ $lease->is_active ? 'badge-success text-white' : 'badge-neutral' }}">
                    {{ $lease->is_active ? 'Active Lease' : 'Expired' }}
                </span>
            </div>

            <div class="text-xs space-y-1 text-base-content/80 bg-base-200/50 p-3 rounded-2xl border border-base-200">
                <div><strong>Contract Period:</strong> {{ $lease->start_date->format('M d, Y') }} - {{ $lease->end_date->format('M d, Y') }}</div>
                <div><strong>Monthly Rate:</strong> ₱{{ number_format($lease->stall->price, 2) }}</div>
                <div><strong>Renewal Status:</strong> {{ ucfirst($lease->renewal_status) }}</div>
            </div>

            <div class="flex items-center justify-between pt-2">
                @if($lease->contract_document)
                <a href="{{ route('file.contract', $lease->id) }}" target="_blank" class="text-xs font-bold link link-primary flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> Contract PDF
                </a>
                @else
                <span class="text-xs text-base-content/40 font-medium">No Document</span>
                @endif

                @if($lease->is_active && $lease->renewal_status !== 'requested')
                <button wire:click="openRenewalModal({{ $lease->id }})" class="btn btn-primary btn-xs rounded-xl font-bold">
                    Request Renewal
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-2 p-12 text-center text-xs font-semibold text-base-content/40 card bg-base-100 rounded-3xl border border-base-300">
            No active lease contracts assigned to your account.
        </div>
        @endforelse
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

