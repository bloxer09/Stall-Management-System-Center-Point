<div class="space-y-6">
    @if(session()->has('error'))
    <div class="alert alert-error shadow-xs rounded-2xl text-xs font-semibold text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-4 w-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 card bg-base-100 p-6 rounded-3xl border border-base-300 shadow-md">
        <div>
            <h2 class="text-xl font-black tracking-tight text-base-content">My Invoices & Online Payments</h2>
            <p class="text-xs text-base-content/60 mt-1">Select pending invoices to pay via PayMongo online checkout or download official PDF receipts.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="stat bg-base-200/60 py-2 px-4 rounded-2xl border border-base-300">
                <div class="stat-title text-[10px] font-extrabold uppercase text-base-content/60">Pending Balance</div>
                <div class="stat-value text-lg font-black text-warning">₱{{ number_format($pendingAmount, 2) }}</div>
            </div>
            @if(count($selectedInvoiceIds) > 0)
            <button wire:click="payWithPayMongo" class="btn btn-primary rounded-xl font-bold text-xs shadow-md gap-2">
                💳 Pay Selected Total: ₱{{ number_format($totalSelected, 2) }}
            </button>
            @endif
        </div>
    </div>

    <!-- Invoices Data Table Container -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md overflow-hidden">
        <!-- Search & Filter Bar -->
        <div class="p-4 bg-base-200/50 border-b border-base-300 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="w-full md:w-1/3 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search invoice #, stall or notes..." class="input input-bordered w-full rounded-2xl text-sm pl-9" />
                <svg class="w-4 h-4 absolute left-3 top-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <button wire:click="selectAllPending" class="btn btn-ghost btn-xs text-primary font-bold">Select All Pending</button>
                <select wire:model.live="statusFilter" class="select select-bordered rounded-2xl text-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending Invoices</option>
                    <option value="paid">Paid History</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70">
                        <th class="w-12 text-center">Select</th>
                        <th class="font-bold">Invoice #</th>
                        <th class="font-bold">Stall</th>
                        <th class="font-bold">Description / Notes</th>
                        <th class="font-bold">Due / Paid Date</th>
                        <th class="font-bold">Amount</th>
                        <th class="font-bold">Status</th>
                        <th class="text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($invoices as $inv)
                    <tr class="hover:bg-base-200/40 transition {{ in_array($inv->id, $selectedInvoiceIds) ? 'bg-primary/5' : '' }}">
                        <td class="text-center">
                            @if($inv->status === 'pending')
                            <input type="checkbox" wire:click="toggleInvoice({{ $inv->id }})" {{ in_array($inv->id, $selectedInvoiceIds) ? 'checked' : '' }} class="checkbox checkbox-primary checkbox-sm rounded-lg">
                            @else
                            <span class="text-base-content/30">&bull;</span>
                            @endif
                        </td>
                        <td class="font-mono text-sm font-bold text-primary">#INV-{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="font-extrabold text-base-content">Stall #{{ $inv->stall->stall_number ?? 'N/A' }}</td>
                        <td class="text-xs text-base-content/80 font-medium max-w-xs truncate">{{ $inv->payment_notes ?: 'Monthly Stall Rental' }}</td>
                        <td class="text-xs font-mono text-base-content/70">
                            {{ $inv->status === 'paid' ? ($inv->paid_at ? $inv->paid_at->format('M d, Y') : 'Paid') : ($inv->due_date ? $inv->due_date->format('M d, Y') : 'N/A') }}
                        </td>
                        <td class="font-bold text-primary">₱{{ number_format($inv->amount, 2) }}</td>
                        <td>
                            <span class="badge badge-md font-bold uppercase {{ $inv->status === 'paid' ? 'badge-success text-white' : ($inv->status === 'pending' ? 'badge-warning' : 'badge-error text-white') }}">
                                {{ $inv->status }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if($inv->status === 'paid')
                            <a href="{{ route('receipt.pdf', $inv->id) }}" target="_blank" class="btn btn-xs btn-outline rounded-xl font-bold gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> Receipt PDF
                            </a>
                            @else
                            <button wire:click="toggleInvoice({{ $inv->id }})" class="btn btn-xs {{ in_array($inv->id, $selectedInvoiceIds) ? 'btn-primary' : 'btn-ghost text-primary' }} rounded-xl font-bold">
                                {{ in_array($inv->id, $selectedInvoiceIds) ? 'Selected' : 'Select' }}
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-12 text-center text-xs font-semibold text-base-content/50">
                            No invoices or payment history found matching criteria.
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
</div>
