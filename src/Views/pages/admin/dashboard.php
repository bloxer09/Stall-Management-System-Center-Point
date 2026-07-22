<div class="space-y-6">
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body flex-row items-center justify-between py-3">
            <h2 class="card-title text-lg">Dashboard Overview</h2>
            <form action="<?= \App\Config\App::basePath() ?>/admin/dashboard" method="GET">
                <select name="period" onchange="this.form.submit()" class="select select-bordered select-sm">
                    <!-- <option value="7days" <?= $stats['period'] === '7days' ? 'selected' : '' ?>>Last 7 Days</option> -->
                    <option value="1month" <?= $stats['period'] === '1month' ? 'selected' : '' ?>>This Month</option>
                    <option value="1year" <?= $stats['period'] === '1year' ? 'selected' : '' ?>>This Year</option>
                </select>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="card-body p-4 flex-row items-center justify-between">
                <div>
                    <div class="text-sm opacity-60 font-medium">Total Stalls</div>
                    <div class="text-2xl font-bold text-primary"><?= number_format($stats['total_stalls']) ?></div>
                    <div class="text-xs mt-1"><a href="<?= \App\Config\App::basePath() ?>/admin/stalls"
                            class="link link-primary">View all stalls</a></div>
                </div>
                <div class="text-primary opacity-20"><i data-lucide="store" class="w-10 h-10"></i></div>
            </div>
        </div>
        <div class="card bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="card-body p-4 flex-row items-center justify-between">
                <div>
                    <div class="text-sm opacity-60 font-medium">Occupied</div>
                    <div class="text-2xl font-bold text-success"><?= number_format($stats['occupied_stalls']) ?></div>
                    <div class="text-xs mt-1 opacity-60">
                        <?= $stats['total_stalls'] > 0 ? round(($stats['occupied_stalls'] / $stats['total_stalls']) * 100) : 0 ?>%
                        occupancy</div>
                </div>
                <div class="text-success opacity-20"><i data-lucide="check-circle" class="w-10 h-10"></i></div>
            </div>
        </div>
        <div class="card bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="card-body p-4 flex-row items-center justify-between">
                <div>
                    <div class="text-sm opacity-60 font-medium">Pending Payments</div>
                    <div class="text-2xl font-bold text-warning"><?= number_format($stats['pending_payments']) ?></div>
                    <div class="text-xs mt-1"><a href="<?= \App\Config\App::basePath() ?>/admin/payments"
                            class="link link-primary">Review payments</a></div>
                </div>
                <div class="text-warning opacity-20"><i data-lucide="clock" class="w-10 h-10"></i></div>
            </div>
        </div>
        <div class="card bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="card-body p-4 flex-row items-center justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="text-sm opacity-60 font-medium">Revenue</div>
                    <div class="text-2xl font-bold text-secondary truncate"
                        title="PHP <?= number_format((float) $stats['revenue_total'], 2) ?>">PHP
                        <?= number_format((float) $stats['revenue_total'], 2) ?></div>
                    <div class="text-xs mt-1 opacity-60">Selected period</div>
                </div>
                <div class="text-secondary opacity-20 shrink-0"><i data-lucide="banknote" class="w-10 h-10"></i></div>
            </div>
        </div>
    </div>

    <!-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="card-title text-base">Delinquency Rate</h3>
                <div class="text-3xl font-bold"><?= number_format((float) ($stats['delinquency_rate'] ?? 0), 2) ?>%</div>
                <p class="text-sm opacity-60"><?= (int) ($stats['overdue_invoices'] ?? 0) ?> overdue pending invoice(s)</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="card-title text-base">Ticket SLA - Overdue</h3>
                <div class="text-3xl font-bold text-warning"><?= number_format((int) ($ticketSla['overdue_open'] ?? 0)) ?></div>
                <p class="text-sm opacity-60">Open/in-progress tickets older than 48h</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="card-title text-base">Avg First Response</h3>
                <div class="text-3xl font-bold text-info">
                    <?php if ($ticketSla['avg_first_response_minutes'] !== null): ?>
                        <?= number_format((float) $ticketSla['avg_first_response_minutes'], 1) ?> min
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
                <p class="text-sm opacity-60">Average time to first staff/admin reply</p>
            </div>
        </div>
    </div> -->

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <div class="flex justify-between items-center mb-4">
                <h3 class="card-title">Support Tickets</h3>
                <a href="<?= \App\Config\App::basePath() ?>/admin/tickets" class="btn btn-ghost btn-sm">Manage
                    Tickets</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="text-center p-4 rounded-box bg-base-200">
                    <div class="text-3xl font-bold"><?= number_format($ticketStats['total']) ?></div>
                    <div class="text-xs font-medium uppercase tracking-wide opacity-60 mt-1">Total</div>
                </div>
                <div class="text-center p-4 rounded-box bg-warning/10">
                    <div class="text-3xl font-bold text-warning"><?= number_format($ticketStats['open']) ?></div>
                    <div class="text-xs font-medium uppercase tracking-wide opacity-60 mt-1">Open</div>
                </div>
                <div class="text-center p-4 rounded-box bg-info/10">
                    <div class="text-3xl font-bold text-info"><?= number_format($ticketStats['in_progress']) ?></div>
                    <div class="text-xs font-medium uppercase tracking-wide opacity-60 mt-1">In Progress</div>
                </div>
                <div class="text-center p-4 rounded-box bg-success/10">
                    <div class="text-3xl font-bold text-success"><?= number_format($ticketStats['resolved']) ?></div>
                    <div class="text-xs font-medium uppercase tracking-wide opacity-60 mt-1">Resolved</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($expiringLeases)): ?>
        <div class="alert alert-warning shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h3 class="font-bold text-sm">Expiring Leases (Next 30 Days)</h3>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($expiringLeases as $lease): ?>
                        <li>
                            Stall <?= htmlspecialchars($lease['stall_number']) ?>
                            <?php if (!empty($lease['location_name'])): ?>(<?= htmlspecialchars($lease['location_name']) ?>)<?php endif; ?>
                            (<?= htmlspecialchars($lease['user_name']) ?>) - Expires
                            <?= date('M d, Y', strtotime($lease['end_date'])) ?>
                            <a href="<?= \App\Config\App::basePath() ?>/admin/mappings/edit/<?= $lease['id'] ?>"
                                class="link font-medium ml-1">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <a href="<?= \App\Config\App::basePath() ?>/admin/stalls/create"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                        <i data-lucide="plus" class="text-primary w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Add Stall</p>
                </a>
                <a href="<?= \App\Config\App::basePath() ?>/admin/stall-locations"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-info/10 flex items-center justify-center mb-3 group-hover:bg-info/20 transition-colors">
                        <i data-lucide="map" class="text-info w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Locations</p>
                </a>
                <a href="<?= \App\Config\App::basePath() ?>/admin/mappings/create"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center mb-3 group-hover:bg-success/20 transition-colors">
                        <i data-lucide="user-plus" class="text-success w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Assign Tenant</p>
                </a>
                <a href="<?= \App\Config\App::basePath() ?>/admin/tickets"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-warning/10 flex items-center justify-center mb-3 group-hover:bg-warning/20 transition-colors">
                        <i data-lucide="message-square" class="text-warning w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Tickets</p>
                </a>
                <a href="<?= \App\Config\App::basePath() ?>/admin/payments"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-secondary/10 flex items-center justify-center mb-3 group-hover:bg-secondary/20 transition-colors">
                        <i data-lucide="receipt" class="text-secondary w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Payments</p>
                </a>
                <a href="<?= \App\Config\App::basePath() ?>/admin/error-logs"
                    class="flex flex-col items-center justify-center p-6 rounded-box border border-base-200 hover:bg-base-200 transition-colors group">
                    <div
                        class="w-12 h-12 rounded-full bg-error/10 flex items-center justify-center mb-3 group-hover:bg-error/20 transition-colors">
                        <i data-lucide="triangle-alert" class="text-error w-6 h-6"></i></div>
                    <p class="font-medium text-sm">Error Logs</p>
                </a>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h3 class="card-title">Revenue Trends</h3>
            <div class="relative h-72 w-full mt-4">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= $chartLabels ?>,
                datasets: [{
                    label: 'Revenue (PHP)',
                    data: <?= $chartData ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.10)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                    fill: true,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'PHP ' + Number(value).toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Revenue: PHP ' + Number(context.parsed.y).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>