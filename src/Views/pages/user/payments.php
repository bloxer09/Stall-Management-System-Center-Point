<div class="space-y-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= \App\Config\App::basePath() ?>/user/payments" method="GET" class="join w-full sm:w-auto">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy ?? 'p.created_at') ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sortOrder ?? 'DESC') ?>">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search payments..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/user/payments" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th><a href="?sort=p.created_at&order=<?= ($sortBy ?? '') === 'p.created_at' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Created <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.due_date&order=<?= ($sortBy ?? '') === 'p.due_date' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Due Date <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.paid_at&order=<?= ($sortBy ?? '') === 'p.paid_at' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Paid At <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=s.stall_number&order=<?= ($sortBy ?? '') === 's.stall_number' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Stall <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Location</th>
                        <th><a href="?sort=p.amount&order=<?= ($sortBy ?? '') === 'p.amount' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Amount <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.status&order=<?= ($sortBy ?? '') === 'p.status' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.payment_method&order=<?= ($sortBy ?? '') === 'p.payment_method' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Method <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="10" class="text-center py-12 opacity-50"><?= !empty($search) ? 'No records match.' : 'No payment history available.' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr class="hover">
                                <td class="text-sm opacity-70"><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                                <td class="text-sm"><?= !empty($payment['due_date']) ? date('M d, Y', strtotime($payment['due_date'])) : '<span class="opacity-40">N/A</span>' ?></td>
                                <td class="text-sm"><?= !empty($payment['paid_at']) ? date('M d, Y H:i', strtotime($payment['paid_at'])) : '<span class="opacity-40">Not paid</span>' ?></td>
                                <td><div class="badge badge-ghost badge-sm"><?= htmlspecialchars($payment['stall_number']) ?></div></td>
                                <td><?= !empty($payment['location_name']) ? htmlspecialchars($payment['location_name']) : '<span class="opacity-40">Unassigned</span>' ?></td>
                                <td class="font-medium">PHP <?= number_format((float)$payment['amount'], 2) ?></td>
                                <td>
                                    <?php
                                        $badge = 'badge-ghost';
                                        if ($payment['status'] === 'paid') $badge = 'badge-success';
                                        if ($payment['status'] === 'failed') $badge = 'badge-error';
                                        if ($payment['status'] === 'pending') $badge = 'badge-warning';
                                    ?>
                                    <div class="badge <?= $badge ?> badge-sm"><?= ucfirst(htmlspecialchars($payment['status'])) ?></div>
                                </td>
                                <td>
                                    <?php
                                        $method = $payment['payment_method'] ?: (!empty($payment['paymongo_id']) ? 'online' : null);
                                        $methodBadge = 'badge-ghost';
                                        if ($method === 'office') $methodBadge = 'badge-info';
                                        if ($method === 'online') $methodBadge = 'badge-secondary';
                                    ?>
                                    <?php if ($method): ?>
                                        <div class="badge <?= $methodBadge ?> badge-sm"><?= ucfirst(htmlspecialchars($method)) ?></div>
                                    <?php else: ?>
                                        <span class="opacity-40">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-xs font-mono opacity-50"><?= htmlspecialchars($payment['payment_reference'] ?: $payment['paymongo_id'] ?: 'N/A') ?></td>
                                <td class="text-right">
                                    <?php if ($payment['status'] === 'paid'): ?>
                                        <a href="<?= \App\Config\App::basePath() ?>/payment/receipt/<?= $payment['id'] ?>" class="btn btn-ghost btn-xs"><i data-lucide="download" class="w-4 h-4"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php require_once __DIR__ . '/../../components/pagination.php'; ?>
    </div>
</div>

