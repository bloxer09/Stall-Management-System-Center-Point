<div class="space-y-8">
    <!-- Header with Export Actions -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Analytics &amp; Insights</h1>
            <p class="text-xs text-base-content/60 mt-1">Financial trajectory, occupancy rates, and collection health.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportXlsx" class="btn btn-outline btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
                <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel (.xlsx)
            </button>
            <button wire:click="exportPdf" class="btn btn-primary btn-sm rounded-xl font-bold gap-1.5 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Summary PDF
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Revenue -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Total Revenue</span>
                <div class="w-9 h-9 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-base-content">₱{{ number_format($totalRevenue, 2) }}</div>
                <div class="text-xs text-success font-semibold mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $totalPaidInvoicesCount }} Settled Invoices
                </div>
            </div>
        </div>

        <!-- Metric 2: Monthly Income & Target -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">{{ date('M Y') }} Income</span>
                <div class="w-9 h-9 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">₱{{ number_format($monthlyRevenue, 2) }}</div>
                <div class="text-xs text-base-content/60 font-semibold mt-1">
                    {{ $collectionRate }}% of ₱{{ number_format($monthlyTarget, 0) }} target
                </div>
            </div>
        </div>

        <!-- Metric 3: Pending Receivables -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Pending Receivables</span>
                <div class="w-9 h-9 rounded-2xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400">₱{{ number_format($pendingReceivables, 2) }}</div>
                <div class="text-xs text-base-content/60 font-semibold mt-1">
                    {{ $pendingInvoicesCount }} Pending Invoices
                </div>
            </div>
        </div>

        <!-- Metric 4: Occupancy Rate -->
        <div class="card bg-base-100 p-5 rounded-3xl border border-base-300 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-base-content/60">Occupancy Rate</span>
                <div class="w-9 h-9 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-black text-primary">{{ $occupancyRate }}%</div>
                <div class="text-xs text-base-content/60 font-semibold mt-1 flex items-center gap-1.5">
                    <span>{{ $occupiedStalls }}/{{ $totalStalls }} Stalls</span>
                    <span class="badge badge-success badge-xs font-bold text-white">{{ $availableStalls }} Vacant</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Revenue Chart (2 cols) -->
        <div class="lg:col-span-2 card bg-base-100 p-6 rounded-3xl shadow-sm border border-base-300 flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                <div>
                    <h3 class="font-extrabold text-base text-base-content">Revenue &amp; Collection Trend</h3>
                    <p class="text-xs text-base-content/60">Rental income growth over the selected timeframe</p>
                </div>
                <!-- Timeframe Pills -->
                <div class="join bg-base-200 p-1 rounded-2xl">
                    <button wire:click="setTimeframe('30d')" class="join-item btn btn-xs rounded-xl font-bold {{ $timeframe === '30d' ? 'btn-primary shadow-xs' : 'btn-ghost' }}">30D</button>
                    <button wire:click="setTimeframe('6m')" class="join-item btn btn-xs rounded-xl font-bold {{ $timeframe === '6m' ? 'btn-primary shadow-xs' : 'btn-ghost' }}">6M</button>
                    <button wire:click="setTimeframe('12m')" class="join-item btn btn-xs rounded-xl font-bold {{ $timeframe === '12m' ? 'btn-primary shadow-xs' : 'btn-ghost' }}">12M</button>
                    <button wire:click="setTimeframe('ytd')" class="join-item btn btn-xs rounded-xl font-bold {{ $timeframe === 'ytd' ? 'btn-primary shadow-xs' : 'btn-ghost' }}">YTD</button>
                </div>
            </div>
            <div class="h-64 relative">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Payment Channel Breakdown (1 col) -->
        <div class="card bg-base-100 p-6 rounded-3xl shadow-sm border border-base-300 flex flex-col justify-between">
            <div class="mb-3">
                <h3 class="font-extrabold text-base text-base-content">Payment Channels</h3>
                <p class="text-xs text-base-content/60">Online Gateways vs Cash settlements</p>
            </div>
            <div class="h-52 relative flex items-center justify-center">
                <canvas id="paymentChannelChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-3 border-t border-base-200 text-center text-xs">
                <div class="p-2 bg-base-200/40 rounded-xl">
                    <span class="text-[10px] font-extrabold uppercase text-primary block">Online / PayMongo</span>
                    <span class="font-extrabold text-sm text-base-content mt-0.5 block">₱{{ number_format($paymentMethodsData[0], 2) }}</span>
                </div>
                <div class="p-2 bg-base-200/40 rounded-xl">
                    <span class="text-[10px] font-extrabold uppercase text-emerald-500 block">Cash / OTC</span>
                    <span class="font-extrabold text-sm text-base-content mt-0.5 block">₱{{ number_format($paymentMethodsData[1], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Deep Dive Insights (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Floor Level Utilization Breakdown -->
        <div class="card bg-base-100 p-6 rounded-3xl shadow-sm border border-base-300 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-base text-base-content">Floor Level Space Utilization</h3>
                    <p class="text-xs text-base-content/60">Occupancy rate and monthly rental yield by floor</p>
                </div>
                <a href="{{ route('admin.floors') }}" wire:navigate class="text-xs text-primary font-bold hover:underline">
                    Manage Floors &rarr;
                </a>
            </div>

            <div class="space-y-4 pt-1">
                @forelse($floorPerformance as $floor)
                    <div class="p-4 bg-base-200/40 rounded-2xl border border-base-300 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 font-black text-base-content">
                                <span>{{ $floor['name'] }}</span>
                                <span class="badge badge-sm font-bold {{ $floor['occupancy_pct'] >= 75 ? 'badge-success text-white' : ($floor['occupancy_pct'] > 0 ? 'badge-primary' : 'badge-neutral') }}">
                                    {{ $floor['occupancy_pct'] }}%
                                </span>
                            </div>
                            <div class="text-xs text-base-content/70 font-mono">
                                <strong>{{ $floor['occupied_stalls'] }}</strong> / {{ $floor['total_stalls'] }} Stalls Occupied
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-base-300 rounded-full h-2 overflow-hidden">
                            <div class="bg-primary h-2 rounded-full transition-all duration-500" style="width: {{ $floor['occupancy_pct'] }}%"></div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-base-content/60 pt-1">
                            <span>Active Rent: <strong class="text-primary">₱{{ number_format($floor['active_rent'], 2) }}</strong></span>
                            <span>Full Capacity: ₱{{ number_format($floor['potential_rent'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-base-content/40 text-xs font-semibold">
                        No floor records found.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Actionable Alerts & Recent Activity -->
        <div class="space-y-6">
            <!-- Card 1: Upcoming Lease Expirations (Next 60 Days) -->
            <div class="card bg-base-100 p-6 rounded-3xl shadow-sm border border-base-300 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-warning animate-pulse"></div>
                        <h3 class="font-extrabold text-base text-base-content">Upcoming Lease Expirations</h3>
                    </div>
                    <span class="badge badge-warning badge-sm font-bold text-xs">Next 60 Days</span>
                </div>

                <div class="space-y-2">
                    @forelse($expiringLeases as $expLease)
                        @php
                            $daysLeft = (int) now()->diffInDays($expLease->end_date, false);
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-base-200/40 rounded-2xl border border-base-300">
                            <div class="flex items-center gap-3">
                                <div class="avatar">
                                    <div class="w-8 h-8 rounded-full border border-primary/20">
                                        <img src="{{ $expLease->user->avatar_url }}" alt="Tenant">
                                    </div>
                                </div>
                                <div>
                                    <div class="font-extrabold text-xs text-base-content">{{ $expLease->user->name }}</div>
                                    <div class="text-[11px] text-base-content/60">Stall #{{ $expLease->stall->stall_number }} &bull; {{ $expLease->stall->location->name ?? 'Main' }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-sm font-bold {{ $daysLeft <= 15 ? 'badge-error text-white' : 'badge-warning' }}">
                                    {{ $daysLeft }} days left
                                </span>
                                <div class="text-[10px] text-base-content/60 font-mono mt-0.5">
                                    Ends {{ $expLease->end_date->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 bg-success/5 border border-success/20 rounded-2xl text-center text-xs text-success font-bold">
                            All tenant commercial leases are in good standing with no immediate expirations.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Card 2: Recent Settled Payments -->
            <div class="card bg-base-100 p-6 rounded-3xl shadow-sm border border-base-300 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-base text-base-content">Recent Settlements</h3>
                    <a href="{{ route('admin.payments') }}" wire:navigate class="text-xs text-primary font-bold hover:underline">
                        View All &rarr;
                    </a>
                </div>

                <div class="space-y-2">
                    @forelse($recentSettlements as $st)
                        <div class="flex items-center justify-between p-2.5 bg-base-200/30 rounded-2xl border border-base-300 text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-xl bg-success/10 text-success flex items-center justify-center font-bold text-[10px]">
                                    ✓
                                </div>
                                <div>
                                    <div class="font-bold text-base-content">{{ $st->user->name ?? 'Tenant' }}</div>
                                    <div class="text-[10px] text-base-content/60 font-mono">Stall #{{ $st->stall->stall_number ?? 'N/A' }} &bull; {{ $st->invoice_number }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-black text-primary">₱{{ number_format($st->total_amount, 2) }}</div>
                                <div class="text-[10px] text-base-content/50">{{ $st->paid_at ? $st->paid_at->diffForHumans() : 'Paid' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-base-content/40 text-xs font-semibold">
                            No payment settlements recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Engine Script -->
    <script src="{{ asset('assets/js/chart.min.js') }}"></script>
    <script>
        document.addEventListener('livewire:navigated', initAnalyticsCharts);
        document.addEventListener('DOMContentLoaded', initAnalyticsCharts);

        function initAnalyticsCharts() {
            const revCanvas = document.getElementById('revenueTrendChart');
            const chanCanvas = document.getElementById('paymentChannelChart');

            if (!revCanvas || !chanCanvas) return;

            if (window.revenueChartInstance) window.revenueChartInstance.destroy();
            if (window.channelChartInstance) window.channelChartInstance.destroy();

            // 1. Revenue Area Chart
            window.revenueChartInstance = new Chart(revCanvas, {
                type: 'line',
                data: {
                    labels: @js($monthsLabels),
                    datasets: [{
                        label: 'Collected Revenue (₱)',
                        data: @js($revenueData),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 3,
                        pointBackgroundColor: '#6366f1',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return ' ₱' + Number(context.parsed.y).toLocaleString(undefined, {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(150, 150, 150, 0.08)' },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + (value >= 1000 ? (value/1000) + 'k' : value);
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. Payment Channel Doughnut Chart
            window.channelChartInstance = new Chart(chanCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Online / PayMongo', 'Cash / OTC'],
                    datasets: [{
                        data: @js($paymentMethodsData),
                        backgroundColor: ['#6366f1', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 11, weight: 'bold' }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    </script>
</div>
