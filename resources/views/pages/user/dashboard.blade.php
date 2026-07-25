<x-app-layout>
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-wrap gap-2 p-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 text-xs font-bold">
            <a href="{{ route('user.dashboard') }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Dashboard</a>
            <a href="{{ route('user.leases') }}" class="px-4 py-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">My Leases</a>
            <a href="{{ route('user.payments') }}" class="px-4 py-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Invoices & Payments</a>
            <a href="{{ route('user.tickets') }}" class="px-4 py-2 rounded-xl text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">Support Chat</a>
        </div>

        <livewire:user.invoices-checkout />
    </div>
</x-app-layout>
