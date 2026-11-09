<div class="space-y-6" @if($hasPendingOnlinePayments) wire:poll.5s="refreshPayments" @endif>
    @if($hasPendingOnlinePayments)
        <p role="status" class="text-sm text-base-content">Awaiting payment confirmation. Invoice status updates automatically.</p>
    @endif
    <!-- Header with Stats -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">My Invoices</h1>
            <p class="text-xs text-base-content/60 mt-1">Review pending bills, pay rent online, and download receipts.</p>
        </div>

        @if($pendingAmount > 0)
            <div class="flex flex-wrap items-center gap-3">
                <button wire:click="selectAllPending" class="btn btn-sm btn-outline rounded-xl font-bold">
                    {{ $allPendingSelected ? 'Deselect All Pending' : 'Select All Pending' }} ({{ $pendingCount }})
                </button>
                <button wire:click="payWithPayMongo" wire:loading.attr="disabled"
                    {{ empty($selectedInvoiceIds) ? 'disabled' : '' }}
                    class="btn btn-sm btn-primary rounded-xl font-bold shadow-xs gap-1.5" wire:target="payWithPayMongo"><span wire:loading wire:target="payWithPayMongo" class="loading loading-spinner loading-xs"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Pay Selected (₱{{ number_format($totalSelected, 2) }})
                </button>
            </div>
        @endif
    </div>

    <!-- Data Table Container -->
    <div class="card bg-base-100 shadow-md overflow-hidden border border-base-300 rounded-3xl">
        <div
            class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live="search" placeholder="Search invoice, stall, notes..."
                    class="input input-bordered w-full rounded-2xl text-sm pl-9">
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm w-full md:w-auto">
                <option value="">All Invoices</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="overdue">Overdue</option>
            </select>
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-2 lg:hidden" data-mobile-invoices>
            @forelse($invoices as $inv)
                <article wire:key="mobile-invoice-{{ $inv->id }}" class="min-w-0 rounded-2xl border border-base-300 p-4 space-y-3 {{ in_array($inv->id, $selectedInvoiceIds) ? 'bg-primary/5' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="min-w-0 break-words text-sm font-bold text-primary">{{ $inv->invoice_number }}</h2>
                        <div class="flex flex-wrap justify-end gap-1 shrink-0">
                            <span class="badge badge-sm font-bold uppercase {{ $inv->status === 'paid' ? 'badge-success text-white' : ($inv->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">{{ $inv->status }}</span>
                            @if($inv->isOverdue())
                                <span data-overdue-badge class="badge badge-sm badge-error text-white font-bold uppercase">Overdue</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="font-bold text-base-content">Stall #{{ $inv->stall->stall_number ?? 'N/A' }}</p>
                        <p class="text-sm text-base-content/70">{{ $inv->stall->location->name ?? 'Main Floor' }}</p>
                    </div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex flex-wrap justify-between gap-2">
                            <dt class="text-base-content/70">{{ $inv->status === 'paid' ? 'Paid on' : 'Due date' }}</dt>
                            <dd>{{ ($inv->status === 'paid' ? $inv->paid_at : $inv->due_date)?->format('M d, Y') ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex flex-wrap justify-between items-baseline gap-2">
                            <dt class="text-base-content/70">Total amount</dt>
                            <dd class="text-lg font-bold text-primary">₱{{ number_format($inv->total_amount, 2) }}</dd>
                        </div>
                    </dl>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-base-300 pt-3">
                        @if($inv->status === 'pending')
                            <label class="flex min-h-11 items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live.number="selectedInvoiceIds" value="{{ $inv->id }}" aria-label="Select invoice {{ $inv->invoice_number }}" class="checkbox checkbox-primary checkbox-sm rounded-lg">
                                <span class="text-sm">Select invoice</span>
                            </label>
                        @endif
                        <button wire:click="openDetailModal({{ $inv->id }})" class="btn btn-outline btn-sm min-h-11 text-primary rounded-xl ml-auto" aria-label="Manage invoice {{ $inv->invoice_number }}">Manage</button>
                    </div>
                </article>
            @empty
                <div class="sm:col-span-2 py-8 text-center text-base-content/70">
                    <p class="font-bold">No invoices found</p>
                    <p class="text-sm mt-1">Your monthly billing invoices will appear here once generated.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden lg:block overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="w-12 text-center">Select</th>
                        <th class="font-bold">Invoice Code</th>
                        <th class="font-bold">Stall</th>
                        <th class="font-bold">Date</th>
                        <th class="font-bold">Total Amount</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($invoices as $inv)
                        <tr wire:key="desktop-invoice-{{ $inv->id }}"
                            class="hover:bg-base-200/40 transition {{ in_array($inv->id, $selectedInvoiceIds) ? 'bg-primary/5' : '' }}">
                            <td class="text-center">
                                @if($inv->status === 'pending')
                                    <input type="checkbox" wire:model.live.number="selectedInvoiceIds" value="{{ $inv->id }}"
                                        aria-label="Select invoice {{ $inv->invoice_number }}" class="checkbox checkbox-primary checkbox-sm rounded-lg">
                                @else
                                    <span class="text-base-content/30">&bull;</span>
                                @endif
                            </td>
                            <td class="font-mono text-sm font-bold text-primary">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="font-extrabold text-base-content">
                                Stall #{{ $inv->stall->stall_number ?? 'N/A' }}
                                <span class="block text-xs font-normal text-base-content/60">{{ $inv->stall->location->name ?? 'Main Floor' }}</span>
                            </td>
                            <td class="text-xs font-mono text-base-content/70">
                                {{ $inv->status === 'paid' ? ($inv->paid_at ? $inv->paid_at->format('M d, Y') : 'Paid') : ($inv->due_date ? $inv->due_date->format('M d, Y') : 'N/A') }}
                            </td>
                            <td class="font-bold text-primary">₱{{ number_format($inv->total_amount, 2) }}</td>
                            <td>
                                <span
                                    class="badge badge-sm font-bold uppercase {{ $inv->status === 'paid' ? 'badge-success text-white' : ($inv->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                                    {{ $inv->status }}
                                </span>
                                @if($inv->isOverdue())
                                    <span data-overdue-badge class="badge badge-sm badge-error text-white font-bold uppercase ml-1">Overdue</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-table-action wire:click="openDetailModal({{ $inv->id }})">
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
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No invoices found</p>
                                        <p class="text-xs mt-0.5">Your monthly billing invoices will appear here once generated.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-base-300">
            {{ $invoices->links() }}
        </div>
    </div>

    <!-- Modal 1: Invoice Detail & Payment Modal -->
    @if($showDetailModal && $selectedInvoice)
        <dialog class="modal modal-open">
            <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative space-y-5 shadow-2xl">
                <button wire:click="closeDetailModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeDetailModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex flex-col sm:flex-row items-start justify-between gap-3 pr-9">
                    <div class="min-w-0">
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Invoice Details</span>
                        <h3 class="text-xl font-black text-base-content font-mono break-words">{{ $selectedInvoice->invoice_number }}</h3>
                        <p class="text-xs text-base-content/60">Issued on {{ $selectedInvoice->created_at->format('F d, Y') }}</p>
                    </div>
                    <span class="badge badge-md font-bold uppercase {{ $selectedInvoice->status === 'paid' ? 'badge-success text-white' : ($selectedInvoice->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                        {{ $selectedInvoice->status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-base-200/60 rounded-2xl border border-base-300 text-xs">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Assigned Space</span>
                        <span class="font-extrabold text-sm text-base-content mt-0.5 block">Stall #{{ $selectedInvoice->stall->stall_number ?? 'N/A' }}</span>
                        <span class="text-[11px] text-base-content/60">{{ $selectedInvoice->stall->location->name ?? 'Main Floor' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Payment Due</span>
                        <span class="font-mono text-xs font-bold text-base-content mt-0.5 block">{{ $selectedInvoice->due_date ? $selectedInvoice->due_date->format('F d, Y') : 'N/A' }}</span>
                        @if($selectedInvoice->paid_at)
                            <span class="text-[11px] text-success font-bold">Paid on {{ $selectedInvoice->paid_at->format('M d, Y') }}</span>
                        @endif
                    </div>
                    <div class="md:col-span-2 pt-2 border-t border-base-300 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="min-w-0 break-words">
                            <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Description</span>
                            <span class="text-xs text-base-content/80 mt-0.5 block">{{ $selectedInvoice->payment_notes ?: 'Monthly Stall Lease Rental' }}</span>
                        </div>
                        <div class="sm:text-right shrink-0">
                            <span class="text-[10px] font-extrabold uppercase text-base-content/60 block">Total Due</span>
                            <span class="text-lg font-black text-primary block">₱{{ number_format($selectedInvoice->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-base-200">
                    <div>
                        @if($selectedInvoice->status === 'paid')
                            <a href="{{ route('receipt.pdf', $selectedInvoice->id) }}" target="_blank" class="btn btn-primary btn-outline btn-sm rounded-xl font-bold gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Download Receipt PDF
                            </a>
                        @elseif($selectedInvoice->status === 'pending')
                            <button wire:click="paySingleInvoice({{ $selectedInvoice->id }})" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs" wire:target="paySingleInvoice"><span wire:loading wire:target="paySingleInvoice" class="loading loading-spinner loading-xs"></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                Pay with PayMongo / GCash
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
