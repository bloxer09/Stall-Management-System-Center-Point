<?php $appName = \App\Config\App::name(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-7 h-7 text-primary"></i>
                Financial Analytics & Occupancy Dashboard
            </h1>
            <p class="text-sm opacity-60">Visual overview of revenue collection trends, payment methods, and stall occupancy performance.</p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-figure text-primary">
                <i data-lucide="dollar-sign" class="w-8 h-8"></i>
            </div>
            <div class="stat-title text-xs font-semibold uppercase">Total Revenue (This Month)</div>
            <div class="stat-value text-primary text-2xl">₱<?= number_format($revenueStats, 2) ?></div>
            <div class="stat-desc text-xs">Collected from verified payments</div>
        </div>

        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-figure text-secondary">
                <i data-lucide="store" class="w-8 h-8"></i>
            </div>
            <div class="stat-title text-xs font-semibold uppercase">Occupancy Rate</div>
            <div class="stat-value text-secondary text-2xl"><?= $stallsSummary['occupancy_rate'] ?? 0 ?>%</div>
            <div class="stat-desc text-xs"><?= $stallsSummary['occupied'] ?? 0 ?> of <?= $stallsSummary['total'] ?? 0 ?> stalls occupied</div>
        </div>

        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-figure text-accent">
                <i data-lucide="check-circle-2" class="w-8 h-8"></i>
            </div>
            <div class="stat-title text-xs font-semibold uppercase">Available Stalls</div>
            <div class="stat-value text-accent text-2xl"><?= $stallsSummary['available'] ?? 0 ?></div>
            <div class="stat-desc text-xs">Ready for tenant leasing</div>
        </div>

        <div class="stat bg-base-100 shadow-md rounded-box border border-base-200">
            <div class="stat-figure text-warning">
                <i data-lucide="wrench" class="w-8 h-8"></i>
            </div>
            <div class="stat-title text-xs font-semibold uppercase">Under Maintenance</div>
            <div class="stat-value text-warning text-2xl"><?= $stallsSummary['maintenance'] ?? 0 ?></div>
            <div class="stat-desc text-xs">Stalls undergoing repair</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Revenue Trend Chart -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-5 h-5 text-primary"></i>
                    Monthly Revenue Trend (12 Months)
                </h3>
                <div class="h-64 mt-2">
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payment Method Breakdown Chart -->
        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <h3 class="card-title text-base font-bold flex items-center gap-2">
                    <i data-lucide="pie-chart" class="w-5 h-5 text-secondary"></i>
                    Payment Methods Distribution
                </h3>
                <div class="h-64 mt-2 flex items-center justify-center">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function initAnalyticsCharts() {
        const trendData = <?= json_encode($monthlyTrends ?? []) ?>;
        const methodData = <?= json_encode($methodBreakdown ?? []) ?>;

        // 1. Revenue Trend Chart
        const trendCtx = document.getElementById('revenueTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.map(item => item.month_label),
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: trendData.map(item => parseFloat(item.total)),
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 3,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // 2. Payment Method Chart
        const methodCtx = document.getElementById('paymentMethodChart');
        if (methodCtx) {
            new Chart(methodCtx, {
                type: 'doughnut',
                data: {
                    labels: methodData.map(item => item.method ? item.method.toUpperCase() : 'ONLINE'),
                    datasets: [{
                        data: methodData.map(item => parseFloat(item.total)),
                        backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    })();
</script>
