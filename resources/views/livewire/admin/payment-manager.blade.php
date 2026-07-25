<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Payment Ledger & Transactions</h1>
            <p class="text-xs text-base-content/60 mt-1">Track PayMongo online checkouts, office cash payments, and billing status</p>
        </div>
        <button wire:click="openManualModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record Office Cash Payment
        </button>
    </div>

    @if(session()->has('message'))
        <div class="alert alert-success shadow-xs rounded-2xl text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Payments Data Table -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search tenant or stall..." class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-left text-xs">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 font-bold uppercase">
                        <th>Invoice #</th>
                        <th>Tenant</th>
                        <th>Stall</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($payments as $p)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono font-bold text-primary">#INV-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="font-bold text-base-content">{{ $p->user->name ?? 'Deleted User' }}</td>
                            <td class="font-medium text-base-content/70">Stall #{{ $p->stall->stall_number ?? 'N/A' }}</td>
                            <td class="font-black text-primary">₱{{ number_format($p->amount, 2) }}</td>
                            <td class="uppercase text-[11px] font-semibold text-base-content/60">{{ $p->payment_method ?? 'paymongo' }}</td>
                            <td>
                                <span class="badge badge-sm font-bold uppercase {{ $p->status === 'paid' ? 'badge-success text-white' : ($p->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                                    {{ $p->status }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if($p->status !== 'paid')
                                    <button wire:click="markAsPaid({{ $p->id }})" class="btn btn-xs btn-outline btn-success rounded-xl font-bold">
                                        Mark Paid
                                    </button>
                                @else
                                    <a href="{{ route('receipt.pdf', $p->id) }}" target="_blank" class="btn btn-xs btn-ghost text-primary font-bold">
                                        PDF Receipt
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-base-content/40 text-xs font-semibold">No payment transaction records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $payments->links() }}
        </div>
    </div>

    <!-- Manual Payment Modal -->
    @if($showManualModal)
    <dialog class="modal modal-open">
        <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative">
            <button wire:click="requestCloseModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            @if($showConfirmClose)
            <div class="p-4 bg-error/10 border border-error/30 rounded-2xl mb-4 text-center space-y-2">
                <h4 class="font-extrabold text-sm text-base-content">Discard Unrecorded Payment Record?</h4>
                <p class="text-xs text-base-content/70">The entered payment details will be cleared.</p>
                <div class="flex gap-2 justify-center pt-2">
                    <button wire:click="cancelConfirmClose" class="btn btn-xs btn-outline rounded-lg">Resume Record</button>
                    <button wire:click="closeModal" class="btn btn-xs btn-error text-white rounded-lg">Yes, Discard</button>
                </div>
            </div>
            @endif

            <h3 class="font-extrabold text-lg text-base-content mb-4">Record Office Cash Payment</h3>
            <form wire:submit="recordManualPayment" class="space-y-4">
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-bold text-xs">Tenant Account</span></label>
                    <select wire:model="user_id" required class="select select-bordered select-sm w-full rounded-xl text-xs">
                        <option value="">-- Select Tenant --</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-bold text-xs">Stall</span></label>
                    <select wire:model="stall_id" required class="select select-bordered select-sm w-full rounded-xl text-xs">
                        <option value="">-- Select Stall --</option>
                        @foreach($stalls as $st)
                            <option value="{{ $st->id }}">Stall #{{ $st->stall_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-bold text-xs">Amount Received (₱)</span></label>
                    <input type="number" step="0.01" wire:model="amount" required placeholder="0.00" class="input input-bordered input-sm w-full rounded-xl text-xs" />
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text font-bold text-xs">Cash Reference #</span></label>
                    <input type="text" wire:model="payment_reference" required placeholder="OFFICE-CASH-1002" class="input input-bordered input-sm w-full rounded-xl text-xs" />
                </div>

                <button type="submit" class="btn btn-primary w-full rounded-xl font-bold text-sm">Record Payment</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="requestCloseModal">close</button></form>
    </dialog>
    @endif
</div>

