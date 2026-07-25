<div class="space-y-8">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Advanced Financial Reporting & Analytics</h2>
            <p class="text-gray-600 text-sm">Comprehensive financial insights, revenue trend charts, aging reports, and export options.</p>
        </div>
        <div class="flex gap-2">
            <button wire:click="exportCsv" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm shadow flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export CSV
            </button>
            <button wire:click="exportPdf" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm shadow flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Export Summary PDF
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-indigo-500">
            <div class="text-xs text-gray-500 font-semibold uppercase">Total Revenue Collected</div>
            <div class="text-2xl font-bold text-indigo-700 mt-1">₱{{ number_format($totalRevenue, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-green-500">
            <div class="text-xs text-gray-500 font-semibold uppercase">Current Month Revenue</div>
            <div class="text-2xl font-bold text-green-700 mt-1">₱{{ number_format($monthlyRevenue, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-blue-500">
            <div class="text-xs text-gray-500 font-semibold uppercase">Occupancy Rate</div>
            <div class="text-2xl font-bold text-blue-700 mt-1">
                {{ $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100, 1) : 0 }}%
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $occupiedStalls }} of {{ $totalStalls }} Stalls Occupied</div>
        </div>

        <div class="bg-white rounded-xl shadow p-6 border-l-4 border-yellow-500">
            <div class="text-xs text-gray-500 font-semibold uppercase">Overdue Receivables</div>
            <div class="text-2xl font-bold text-yellow-700 mt-1">
                ₱{{ number_format($overdue30->sum('amount') + $overdue60->sum('amount') + $overdue90->sum('amount'), 2) }}
            </div>
        </div>
    </div>

    <!-- Chart: 12-Month Revenue Trend -->
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">12-Month Revenue Trend Analysis</h3>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <div class="relative h-64">
            <canvas id="revenueChart"></canvas>
        </div>
        <script>
            document.addEventListener('livewire:initialized', () => {
                const ctx = document.getElementById('revenueChart').getContext('2d');
                const trendData = @json($monthlyTrend);
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: trendData.map(d => d.month),
                        datasets: [{
                            label: 'Monthly Revenue (₱)',
                            data: trendData.map(d => d.revenue),
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            });
        </script>
    </div>

    <!-- Overdue Payment Aging Table -->
    <div class="bg-white rounded-xl shadow p-6 space-y-4">
        <h3 class="text-lg font-bold text-gray-800">Overdue Payment Aging Report</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                <div class="font-bold text-yellow-800">1 – 30 Days Overdue</div>
                <div class="text-xl font-bold text-yellow-900 mt-1">₱{{ number_format($overdue30->sum('amount'), 2) }}</div>
                <div class="text-xs text-yellow-700">{{ $overdue30->count() }} invoice(s)</div>
            </div>

            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                <div class="font-bold text-orange-800">31 – 60 Days Overdue</div>
                <div class="text-xl font-bold text-orange-900 mt-1">₱{{ number_format($overdue60->sum('amount'), 2) }}</div>
                <div class="text-xs text-orange-700">{{ $overdue60->count() }} invoice(s)</div>
            </div>

            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <div class="font-bold text-red-800">60+ Days Overdue</div>
                <div class="text-xl font-bold text-red-900 mt-1">₱{{ number_format($overdue90->sum('amount'), 2) }}</div>
                <div class="text-xs text-red-700">{{ $overdue90->count() }} invoice(s)</div>
            </div>
        </div>
    </div>
</div>
