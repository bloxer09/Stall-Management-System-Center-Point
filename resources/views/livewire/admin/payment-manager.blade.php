<div class="space-y-6" @if($hasPendingOnlinePayments) wire:poll.5s="refreshPayments" @endif>
    <!-- Header with Stats -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Payments & Invoices</h1>
            <p class="text-xs text-base-content/60 mt-1">Track rent payments, cash collections, and payment gateways.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="exportXlsx" class="btn btn-outline btn-sm rounded-xl font-bold gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Excel
            </button>
            <button wire:click="openManualModal" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Record Cash Payment
            </button>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search invoice, tenant, stall..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm w-full md:w-auto">
                <option value="">All</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>

        <div class="overflow-x-auto" wire:loading.class="opacity-60" wire:target="search,statusFilter">
            <table class="table w-full text-left text-xs">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="font-bold">Invoice Code</th>
                        <th class="font-bold">Tenant</th>
                        <th class="font-bold">Stall</th>
                        <th class="font-bold">Total Amount</th>
                        <th class="font-bold">Gateway</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($payments as $p)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono font-extrabold text-base-content">{{ $p->invoice_number }}</td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($p->user)
                                        <div class="avatar">
                                            <div
                                                class="w-7 h-7 rounded-full overflow-hidden border border-primary/20 shadow-xs">
                                                <img src="{{ $p->user->avatar_url }}" x-data x-on:click="openImageLightbox($el.src)"
                                                    class="w-full h-full object-cover cursor-pointer" alt="Tenant Avatar"
                                                    title="Click to view full photo">
                                            </div>
                                        </div>
                                    @endif
                                    <span class="font-bold text-base-content">{{ $p->user->name ?? 'Deleted User' }}</span>
                                </div>
                            </td>
                            <td class="font-medium text-base-content/70">Stall #{{ $p->stall->stall_number ?? 'N/A' }}</td>
                            <td class="font-black text-primary text-sm">₱{{ number_format($p->total_amount, 2) }}</td>
                            <td class="uppercase text-[11px] font-semibold text-base-content/60">
                                {{ $p->transactions->last()->gateway ?? ($p->status === 'paid' ? 'cash' : 'paymongo') }}
                            </td>
                            <td>
                                <span
                                    class="badge badge-sm font-bold uppercase {{ $p->status === 'paid' ? 'badge-success text-white' : ($p->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                                    {{ $p->status }}
                                </span>
                                @if($p->isOverdue())
                                    <span data-overdue-badge class="badge badge-sm badge-error text-white font-bold uppercase ml-1">Overdue</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-table-action wire:click="openDetailModal({{ $p->id }})">
                                    Manage
                                </x-table-action>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No payment records found</p>
                                        <p class="text-xs mt-0.5">Payments will appear here once tenants pay their invoices.
                                        </p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $payments->links() }}
        </div>
    </div>

    <!-- Modal 1: Invoice Detail & Actions Modal -->
    @if($showDetailModal && $selectedInvoice)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeDetailModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Invoice Number</span>
                        <h3 class="text-xl font-black text-base-content font-mono">{{ $selectedInvoice->invoice_number }}</h3>
                        <p class="text-xs text-base-content/60">Issued on {{ $selectedInvoice->created_at->format('F d, Y') }}</p>
                    </div>
                    <span class="badge badge-md font-bold uppercase {{ $selectedInvoice->status === 'paid' ? 'badge-success text-white' : ($selectedInvoice->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                        {{ $selectedInvoice->status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4 bg-base-200/60 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Tenant</span>
                        <span class="font-extrabold text-sm text-base-content mt-0.5 block">{{ $selectedInvoice->user->name ?? 'Deleted User' }}</span>
                        <span class="text-[11px] text-base-content/60">{{ $selectedInvoice->user->email ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Stall Space</span>
                        <span class="font-extrabold text-sm text-base-content mt-0.5 block">Stall #{{ $selectedInvoice->stall->stall_number ?? 'N/A' }}</span>
                        <span class="text-[11px] text-base-content/60">{{ $selectedInvoice->stall->location->name ?? 'Main Floor' }}</span>
                    </div>
                    <div class="col-span-2 pt-2 border-t border-base-300 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Due Date</span>
                            <span class="font-mono text-xs font-bold text-base-content mt-0.5 block">{{ $selectedInvoice->due_date ? $selectedInvoice->due_date->format('F d, Y') : 'N/A' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Total Due</span>
                            <span class="text-lg font-black text-primary block">₱{{ number_format($selectedInvoice->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Details -->
                @if($selectedInvoice->transactions->count() > 0)
                    <div class="p-3 bg-base-200/30 rounded-2xl border border-base-300/50 space-y-1 text-xs">
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Latest Transaction</span>
                        @php $tx = $selectedInvoice->transactions->last(); @endphp
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-base-content font-bold">Ref: {{ $tx->reference_number }}</span>
                            <span class="badge badge-xs badge-neutral font-mono uppercase">{{ $tx->gateway }}</span>
                        </div>
                    </div>
                @endif

                <!-- Modal Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-base-200">
                    <div>
                        @if($selectedInvoice->status === 'paid')
                            <a href="{{ route('receipt.pdf', $selectedInvoice->id) }}" target="_blank" class="btn btn-primary btn-outline btn-sm rounded-xl font-bold gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Download Receipt
                            </a>
                        @endif
                    </div>
                    <button wire:click="closeDetailModal" class="btn btn-ghost btn-sm rounded-xl font-bold">Close</button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="closeDetailModal">close</button></form>
        </dialog>
    @endif

    <!-- Modal 2: Manual Cash Payment Modal -->
    @if($showManualModal)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-md p-6 bg-base-100 rounded-3xl border border-base-300 relative shadow-2xl">
                <button wire:click="requestCloseModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.requestCloseModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                @if($showConfirmClose)
                    <div class="p-4 bg-error/10 border border-error/30 rounded-2xl text-center space-y-2 mb-4">
                        <h4 class="font-extrabold text-sm text-base-content">Discard Payment Entry?</h4>
                        <p class="text-xs text-base-content/70">Your entered details will be lost.</p>
                        <div class="flex gap-2 justify-center pt-2">
                            <button wire:click="cancelConfirmClose" class="btn btn-xs btn-outline rounded-lg">Keep Editing</button>
                            <button wire:click="closeModal" class="btn btn-xs btn-error text-white rounded-lg">Yes, Discard</button>
                        </div>
                    </div>
                @endif

                <h3 class="text-lg font-bold text-base-content mb-4">Record Cash Payment</h3>
                <form wire:submit.prevent="recordManualPayment" class="space-y-4">
                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Select Tenant</span></label>
                        <select wire:model="user_id" required class="select select-bordered rounded-xl text-sm">
                            <option value="">Select Tenant</option>
                            @foreach($modalUsers as $u)
                                <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                            @endforeach
                        </select>
                        @error('user_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Select Stall</span></label>
                        <select wire:model="stall_id" required class="select select-bordered rounded-xl text-sm">
                            <option value="">Select Stall</option>
                            @foreach($modalStalls as $st)
                                <option value="{{ $st['id'] }}">Stall #{{ $st['stall_number'] }}</option>
                            @endforeach
                        </select>
                        @error('stall_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Amount Paid (₱)</span></label>
                        <input type="number" step="0.01" wire:model="amount" placeholder="0.00" required
                            class="input input-bordered rounded-xl text-sm">
                        @error('amount') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Payment Reference / OR #</span></label>
                        <input type="text" wire:model="payment_reference" placeholder="e.g. OR-2026-001" required
                            class="input input-bordered rounded-xl text-sm">
                        @error('payment_reference') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Notes</span></label>
                        <textarea wire:model="payment_notes" placeholder="Optional payment details..."
                            class="textarea textarea-bordered rounded-xl text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                        <button type="button" wire:click="requestCloseModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold" wire:loading.attr="disabled" wire:target="recordManualPayment"><span wire:loading wire:target="recordManualPayment" class="loading loading-spinner loading-xs"></span>Save Cash Payment</button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button wire:click="requestCloseModal">close</button></form>
        </dialog>
    @endif
</div>
