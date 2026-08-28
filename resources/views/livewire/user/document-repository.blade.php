<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-black tracking-tight text-base-content">My Documents</h1>
        <p class="text-xs text-base-content/60 mt-1">Download your lease contracts and payment receipts.</p>
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
                            <td class="text-xs font-mono text-base-content/70">{{ $contract->start_date }} to {{ $contract->end_date }}</td>
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
                <h2 class="text-sm font-black text-base-content uppercase tracking-wider">Payment Receipts</h2>
                <p class="text-[10px] text-base-content/50">Official PDF receipts for all confirmed payments</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table w-full text-sm">
                <thead>
                    <tr class="bg-base-200/40 text-base-content/70 text-xs font-bold uppercase tracking-wider">
                        <th>Receipt Ref</th>
                        <th>Stall</th>
                        <th>Amount Paid</th>
                        <th>Date Paid</th>
                        <th class="text-right">Download</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base-200">
                    @forelse($paidReceipts as $receipt)
                        <tr class="hover:bg-base-200/40 transition">
                            <td class="font-mono font-bold text-primary">#REC-{{ str_pad($receipt->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="font-extrabold text-base-content">Stall {{ $receipt->stall->stall_number }}</td>
                            <td class="font-black text-success">₱{{ number_format($receipt->amount, 2) }}</td>
                            <td class="text-xs font-mono text-base-content/70">{{ $receipt->paid_at ? \Carbon\Carbon::parse($receipt->paid_at)->format('M d, Y') : '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('receipt.pdf', $receipt->id) }}" target="_blank"
                                   class="btn btn-xs btn-outline btn-success rounded-xl font-bold gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    PDF Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="flex flex-col items-center gap-3 text-base-content/40">
                                    <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div>
                                        <p class="font-extrabold text-sm">No receipts yet</p>
                                        <p class="text-xs mt-0.5">Your official payment receipts will appear here once payments are confirmed.</p>
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
