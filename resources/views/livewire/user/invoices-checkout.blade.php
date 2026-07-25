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
            <p class="text-xs text-base-content/60 mt-1">Select pending invoices to pay via GCash, Maya, or Debit/Credit card.</p>
        </div>
        @if(count($selectedInvoiceIds) > 0)
        <button wire:click="payWithPayMongo" class="btn btn-primary rounded-xl font-bold text-sm shadow-md gap-2">
            💳 Pay Selected Total: ₱{{ number_format($totalSelected, 2) }}
        </button>
        @endif
    </div>

    <!-- Pending Invoices Section -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 space-y-4 shadow-md">
        <div class="flex justify-between items-center">
            <h3 class="font-bold text-base-content text-base">Pending Invoices</h3>
            @if($pendingInvoices->isNotEmpty())
            <button wire:click="selectAllPending" class="btn btn-ghost btn-xs text-primary font-bold">Select All Pending</button>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($pendingInvoices as $inv)
            <div wire:click="toggleInvoice({{ $inv->id }})" class="p-4 rounded-2xl border cursor-pointer transition flex items-center justify-between {{ in_array($inv->id, $selectedInvoiceIds) ? 'bg-primary/10 border-primary font-semibold' : 'bg-base-200/50 border-base-300 hover:bg-base-200' }}">
                <div class="flex items-center gap-3">
                    <input type="checkbox" {{ in_array($inv->id, $selectedInvoiceIds) ? 'checked' : '' }} class="checkbox checkbox-primary checkbox-sm rounded-lg">
                    <div>
                        <span class="font-bold text-base-content block text-sm">Stall {{ $inv->stall->stall_number }} Invoice</span>
                        <span class="text-xs text-base-content/60">{{ $inv->payment_notes ?: 'Monthly Stall Rental' }} • Due: {{ $inv->due_date ? $inv->due_date->format('M d, Y') : 'N/A' }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-lg font-black text-primary">₱{{ number_format($inv->amount, 2) }}</span>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-xs font-semibold text-base-content/40">No pending invoices! All payments are up to date.</div>
            @endforelse
        </div>
    </div>

    <!-- Paid Invoices History -->
    <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 space-y-4 shadow-md">
        <h3 class="font-bold text-base-content text-base">Payment History & PDF Receipts</h3>
        <div class="divide-y divide-base-200 text-sm">
            @forelse($paidInvoices as $paid)
            <div class="py-3 flex justify-between items-center">
                <div>
                    <span class="font-bold text-base-content">Stall {{ $paid->stall->stall_number }}</span>
                    <span class="text-xs text-base-content/50 block">Paid on {{ $paid->paid_at ? $paid->paid_at->format('M d, Y h:i A') : 'N/A' }} via {{ strtoupper($paid->payment_method ?? 'cash') }}</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="font-black text-success">₱{{ number_format($paid->amount, 2) }}</span>
                    <a href="{{ route('receipt.pdf', $paid->id) }}" target="_blank" class="btn btn-xs btn-outline rounded-xl font-bold gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z"/></svg> PDF Receipt
                    </a>
                </div>
            </div>
            @empty
            <div class="p-4 text-center text-xs text-base-content/40 font-semibold">No payment history yet.</div>
            @endforelse
        </div>
    </div>
</div>

