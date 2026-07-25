<div class="space-y-6">
    <!-- Header with Export Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight">Analytics & Performance Reports</h1>
            <p class="text-xs text-base-content/60 mt-1">Real-time commercial stall occupancy, revenue trends, and operational metrics</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportCsv" class="btn btn-outline btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV Data
            </button>
            <button wire:click="exportPdf" class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Summary PDF
            </button>
        </div>
    </div>

    <!-- Summary Key Metric Cards (DaisyUI Stats Component) -->
    <div class="stats stats-vertical sm:stats-horizontal shadow-sm bg-base-100 border border-base-300 rounded-2xl w-full">
        <div class="stat p-5">
            <div class="stat-figure text-primary">
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Total Revenue</div>
            <div class="stat-value text-2xl font-black text-base-content mt-1">₱{{ number_format($totalRevenue, 2) }}</div>
            <div class="stat-desc text-xs text-success font-semibold mt-1">Lifetime Collected</div>
        </div>

        <div class="stat p-5">
            <div class="stat-figure text-secondary">
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Monthly Income</div>
            <div class="stat-value text-2xl font-black text-primary mt-1">₱{{ number_format($monthlyRevenue, 2) }}</div>
            <div class="stat-desc text-xs text-base-content/50 mt-1">{{ date('F Y') }} Collection</div>
        </div>

        <div class="stat p-5">
            <div class="stat-figure text-accent">
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Occupancy Rate</div>
            <div class="stat-value text-2xl font-black text-emerald-500 mt-1">{{ $occupancyRate }}%</div>
            <div class="stat-desc text-xs text-base-content/60 mt-1">{{ $occupiedStalls }} of {{ $totalStalls }} Stalls Occupied</div>
        </div>

        <div class="stat p-5">
            <div class="stat-title text-xs font-bold text-base-content/60 uppercase tracking-wider">Stall Inventory</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="badge badge-success font-bold text-xs">{{ $availableStalls }} Free</span>
                <span class="badge badge-warning font-bold text-xs">{{ $maintenanceStalls }} Maint</span>
            </div>
            <div class="stat-desc text-xs text-base-content/50 mt-2">Active Stall Units</div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Revenue Collection Trend -->
        <div class="card bg-base-100 p-6 rounded-3xl shadow-md border border-base-300 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-bold text-base text-base-content">12-Month Revenue Collection Trend</h3>
                    <p class="text-xs text-base-content/60">Monthly rental income growth over time (PHP)</p>
                </div>
                <span class="badge badge-primary font-bold text-xs">Line Graph</span>
            </div>
            <div class="h-64 relative">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Floor Occupancy Breakdown -->
        <div class="card bg-base-100 p-6 rounded-3xl shadow-md border border-base-300 flex flex-col justify-between">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="font-bold text-base text-base-content">Floor Level Occupancy Breakdown</h3>
                    <p class="text-xs text-base-content/60">Occupied vs Total capacity stalls per floor level</p>
                </div>
                <span class="badge badge-accent font-bold text-xs">Bar Chart</span>
            </div>
            <div class="h-64 relative">
                <canvas id="floorOccupancyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Include Chart.js CDN for visual rendering -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', initCharts);
        document.addEventListener('DOMContentLoaded', initCharts);

        function initCharts() {
            const revenueCtx = document.getElementById('revenueTrendChart');
            const floorCtx = document.getElementById('floorOccupancyChart');

            if (!revenueCtx || !floorCtx) return;

            if (window.revenueChartInstance) window.revenueChartInstance.destroy();
            if (window.floorChartInstance) window.floorChartInstance.destroy();

            window.revenueChartInstance = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: @js($monthsLabels),
                    datasets: [{
                        label: 'Revenue (₱)',
                        data: @js($revenueData),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.15)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(150, 150, 150, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            window.floorChartInstance = new Chart(floorCtx, {
                type: 'bar',
                data: {
                    labels: @js($locationLabels),
                    datasets: [
                        {
                            label: 'Occupied Stalls',
                            data: @js($locationOccupied),
                            backgroundColor: '#10b981',
                            borderRadius: 6
                        },
                        {
                            label: 'Total Capacity',
                            data: @js($locationTotal),
                            backgroundColor: 'rgba(150, 150, 150, 0.2)',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(150, 150, 150, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    </script>
</div>

