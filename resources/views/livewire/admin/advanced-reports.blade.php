<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Advanced Financial Reports</h1>
            <p class="text-xs text-base-content/60 mt-1">Comprehensive financial insights, revenue trends, receivables aging, and export options.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportCsv"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-wait"
                wire:target="exportCsv"
                class="btn btn-outline btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <span wire:loading wire:target="exportCsv" class="loading loading-spinner loading-xs"></span>
                <svg class="w-4 h-4" wire:loading.remove wire:target="exportCsv" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </button>
            <button wire:click="exportPdf"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-wait"
                wire:target="exportPdf"
                class="btn btn-primary btn-sm rounded-xl font-bold gap-1 shadow-xs">
                <span wire:loading wire:target="exportPdf" class="loading loading-spinner loading-xs"></span>
                <svg class="w-4 h-4" wire:loading.remove wire:target="exportPdf" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Export PDF
            </button>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">Total Revenue</div>
                <div class="text-xl font-black text-primary mt-0.5">₱{{ number_format($totalRevenue, 2) }}</div>
                <div class="text-[10px] text-base-content/50 mt-0.5">All time collected</div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-success/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">This Month</div>
                <div class="text-xl font-black text-success mt-0.5">₱{{ number_format($monthlyRevenue, 2) }}</div>
                <div class="text-[10px] text-base-content/50 mt-0.5">Current billing cycle</div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-info/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">Occupancy Rate</div>
                <div class="text-xl font-black text-info mt-0.5">
                    {{ $totalStalls > 0 ? round(($occupiedStalls / $totalStalls) * 100, 1) : 0 }}%
                </div>
                <div class="text-[10px] text-base-content/50 mt-0.5">{{ $occupiedStalls }} / {{ $totalStalls }} stalls</div>
            </div>
        </div>

        <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-warning/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-base-content/50">Overdue Total</div>
                <div class="text-xl font-black text-warning mt-0.5">
                    ₱{{ number_format($overdue30->sum('amount') + $overdue60->sum('amount') + $overdue90->sum('amount'), 2) }}
                </div>
                <div class="text-[10px] text-base-content/50 mt-0.5">Pending receivables</div>
            </div>
        </div>
    </div>

    <!-- Chart: 12-Month Revenue Trend -->
    <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-black text-base-content uppercase tracking-wider">12-Month Revenue Trend</h2>
            <span class="badge badge-primary badge-sm font-bold">Chart</span>
        </div>
        <div class="relative h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Overdue Aging Report -->
    <div class="card bg-base-100 border border-base-300 shadow-md rounded-3xl p-6 space-y-4">
        <h2 class="text-base font-black text-base-content uppercase tracking-wider">Overdue Payment Aging Report</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-warning/30 bg-warning/5 p-5">
                <div class="text-xs font-extrabold uppercase tracking-wider text-warning mb-1">1 – 30 Days</div>
                <div class="text-2xl font-black text-warning">₱{{ number_format($overdue30->sum('amount'), 2) }}</div>
                <div class="text-[10px] text-base-content/50 mt-1">{{ $overdue30->count() }} invoice(s) overdue</div>
            </div>

            <div class="rounded-2xl border border-orange-400/30 bg-orange-400/5 p-5">
                <div class="text-xs font-extrabold uppercase tracking-wider text-orange-500 mb-1">31 – 60 Days</div>
                <div class="text-2xl font-black text-orange-500">₱{{ number_format($overdue60->sum('amount'), 2) }}</div>
                <div class="text-[10px] text-base-content/50 mt-1">{{ $overdue60->count() }} invoice(s) overdue</div>
            </div>

            <div class="rounded-2xl border border-error/30 bg-error/5 p-5">
                <div class="text-xs font-extrabold uppercase tracking-wider text-error mb-1">60+ Days Critical</div>
                <div class="text-2xl font-black text-error">₱{{ number_format($overdue90->sum('amount'), 2) }}</div>
                <div class="text-[10px] text-base-content/50 mt-1">{{ $overdue90->count() }} invoice(s) overdue</div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        function initRevenueChart() {
            const canvas = document.getElementById('revenueChart');
            if (!canvas) return;
            if (canvas._chartInstance) { canvas._chartInstance.destroy(); }

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
            const labelColor = isDark ? 'rgba(255,255,255,0.5)' : 'rgba(0,0,0,0.4)';

            const trendData = @json($monthlyTrend);
            const ctx = canvas.getContext('2d');

            canvas._chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.month),
                    datasets: [{
                        label: 'Monthly Revenue (₱)',
                        data: trendData.map(d => d.revenue),
                        borderColor: 'hsl(var(--p))',
                        backgroundColor: 'hsla(var(--p), 0.12)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'hsl(var(--p))',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: labelColor, font: { size: 11, weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: labelColor,
                                font: { size: 11 },
                                callback: v => '₱' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)
                            }
                        }
                    }
                }
            });
        }

        // Load Chart.js once, then init
        if (typeof Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = '{{ asset('assets/js/chart.min.js') }}';
            s.onload = initRevenueChart;
            document.head.appendChild(s);
        } else {
            initRevenueChart();
        }

        document.addEventListener('livewire:navigated', initRevenueChart);
    })();
</script>
