<div class="space-y-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">My Document Repository</h2>
        <p class="text-gray-600 text-sm">Secure access to all your official lease contracts, payment receipts, and billing documents.</p>
    </div>

    <!-- Section 1: Lease Contracts -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Lease Contracts
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Contract Period</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Document</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($leaseContracts as $contract)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">Stall {{ $contract->stall->stall_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $contract->start_date }} to {{ $contract->end_date }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-bold {{ $contract->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $contract->is_active ? 'ACTIVE' : 'EXPIRED' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('file.contract', $contract->id) }}" target="_blank" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 font-semibold text-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download Contract PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500">No contract documents uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Official Receipts -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Payment Receipts
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Receipt Ref</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Stall</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Amount Paid</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date Paid</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Download Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($paidReceipts as $receipt)
                        <tr>
                            <td class="px-4 py-3 font-mono font-medium text-gray-900">#REC-{{ str_pad($receipt->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">Stall {{ $receipt->stall->stall_number }}</td>
                            <td class="px-4 py-3 font-bold text-green-700">₱{{ number_format($receipt->amount, 2) }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $receipt->paid_at ? \Carbon\Carbon::parse($receipt->paid_at)->format('M d, Y') : '-' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('receipt.pdf', $receipt->id) }}" target="_blank" class="inline-flex items-center gap-1 text-green-600 hover:text-green-900 font-semibold text-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Download PDF Receipt
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">No paid receipts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
