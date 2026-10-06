<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-black tracking-tight text-base-content">My Documents</h1>
        <p class="text-xs text-base-content/60 mt-1">Download your lease contracts, billing statements, and payment receipts.</p>
    </div>

    <!-- Section 1: Lease Contracts -->
    <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl overflow-hidden">
        <div class="p-5 border-b border-base-300 bg-base-200/30 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-base-content uppercase tracking-wider">Lease Contracts</h2>
                <p class="text-[10px] text-base-content/50">Signed contracts for your stalls</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 text-xs font-bold uppercase tracking-wider">
                        <th>Stall</th>
                        <th>Contract Period</th>
                        <th>Status</th>
                        <th class="text-right">Document</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($leaseContracts as $contract)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-extrabold text-base-content">Stall {{ $contract->stall->stall_number }}</td>
                            <td class="text-xs font-mono text-base-content/70">{{ $contract->start_date?->format('M d, Y') }} to {{ $contract->end_date?->format('M d, Y') }}</td>
                            <td>
                                <span class="badge badge-sm font-extrabold uppercase {{ $contract->is_active ? 'badge-success text-white' : 'badge-neutral' }}">
                                    {{ $contract->is_active ? 'Active' : 'Expired' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('file.contract', $contract->id) }}" target="_blank"
                                   class="btn btn-xs btn-outline rounded-xl font-bold gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No contracts found</p>
                                        <p class="text-xs mt-0.5">Your lease contract documents will appear here once uploaded by management.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Official Receipts -->
    <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl overflow-hidden">
        <div class="p-5 border-b border-base-300 bg-base-200/30 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-success/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-base-content uppercase tracking-wider">Billing statements</h2>
                <p class="text-[10px] text-base-content/50">Every bill for your stalls, with official receipts for paid ones</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 text-xs font-bold uppercase tracking-wider">
                        <th>Invoice</th>
                        <th>Stall</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($statements as $invoice)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono font-bold text-primary">{{ $invoice->invoice_number }}</td>
                            <td class="font-extrabold text-base-content">Stall {{ $invoice->stall->stall_number ?? '—' }}</td>
                            <td class="text-xs text-base-content/70">{{ $invoice->billing_period ?? '—' }}</td>
                            <td class="font-black text-base-content">₱{{ number_format($invoice->total_amount, 2) }}</td>
                            <td>
                                @if($invoice->status === 'paid')
                                    <span class="badge badge-sm badge-success text-white font-bold uppercase">Paid</span>
                                    <div class="text-[10px] text-base-content/50 mt-0.5">{{ $invoice->paid_at?->format('M d, Y') }}</div>
                                @elseif($invoice->isOverdue())
                                    <span data-overdue-badge class="badge badge-sm badge-error text-white font-bold uppercase">Overdue</span>
                                    <div class="text-[10px] text-base-content/50 mt-0.5">Was due {{ $invoice->due_date?->format('M d, Y') }}</div>
                                @else
                                    <span class="badge badge-sm badge-warning font-bold uppercase">Pending</span>
                                    <div class="text-[10px] text-base-content/50 mt-0.5">Due {{ $invoice->due_date?->format('M d, Y') }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($invoice->status === 'paid')
                                    <a href="{{ route('receipt.pdf', $invoice->id) }}" target="_blank"
                                       class="btn btn-xs btn-outline btn-success rounded-xl font-bold gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        PDF Receipt
                                    </a>
                                @else
                                    <a href="{{ route('user.payments') }}" wire:navigate class="btn btn-xs btn-primary rounded-xl font-bold">Pay now</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No bills yet</p>
                                        <p class="text-xs mt-0.5">Your monthly bills and official receipts will appear here.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
