<x-app-layout>
    <div class="py-12 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card bg-base-100 rounded-3xl shadow-xl overflow-hidden border border-base-300">
            <div class="bg-primary px-6 py-8 text-primary-content text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-content/10 rounded-full mb-3">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h1 class="text-2xl font-black">Official Receipt Verified</h1>
                <p class="text-primary-content/80 text-xs mt-1">This payment record is authentic and registered in {{ \App\Models\Setting::get('platform_name', 'STALLM') }} system.</p>
            </div>

            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Receipt Number</span>
                        <span class="text-lg font-mono font-bold text-base-content">#{{ str_pad($payment->id, 8, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Payment Status</span>
                        <span class="badge badge-success text-white font-black text-xs uppercase mt-1">PAID</span>
                    </div>
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Payer Name</span>
                        <span class="font-extrabold text-base-content">{{ $payment->user->name }}</span>
                    </div>
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Stall Number</span>
                        <span class="font-bold text-base-content">Stall {{ $payment->stall->stall_number }} ({{ $payment->stall->location->name ?? 'Main Floor' }})</span>
                    </div>
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Amount Paid</span>
                        <span class="text-xl font-black text-primary">₱{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="p-4 bg-base-200 rounded-2xl border border-base-300">
                        <span class="text-base-content/60 block text-xs font-bold uppercase">Paid At</span>
                        <span class="font-semibold text-base-content">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y h:i A') : 'N/A' }}</span>
                    </div>
                </div>

                @auth
                <div class="text-center pt-4">
                    <a href="{{ route('receipt.pdf', $payment->id) }}" target="_blank" class="btn btn-primary rounded-xl font-bold px-8 shadow-xs">
                        Download PDF Receipt
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>
