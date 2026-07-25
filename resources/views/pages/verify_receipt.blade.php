<x-app-layout>
    <div class="py-12 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="bg-indigo-600 px-6 py-8 text-white text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-full mb-3">
                    <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h1 class="text-2xl font-bold">Official Receipt Verified</h1>
                <p class="text-indigo-100 text-sm mt-1">This payment record is authentic and registered in STALLM system.</p>
            </div>

            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Receipt Number</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">#{{ str_pad($payment->id, 8, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Payment Status</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">PAID</span>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Payer Name</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->user->name }}</span>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Stall Number</span>
                        <span class="font-semibold text-gray-900 dark:text-white">Stall {{ $payment->stall->stall_number }} ({{ $payment->stall->location->name ?? 'Main Floor' }})</span>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Amount Paid</span>
                        <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">₱{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <span class="text-gray-500 dark:text-gray-400 block text-xs font-medium uppercase">Paid At</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y h:i A') : 'N/A' }}</span>
                    </div>
                </div>

                @auth
                <div class="text-center pt-4">
                    <a href="{{ route('receipt.pdf', $payment->id) }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition">
                        Download PDF Receipt
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </div>
</x-app-layout>
