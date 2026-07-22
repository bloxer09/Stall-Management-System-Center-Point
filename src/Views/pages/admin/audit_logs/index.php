<?php
$filters = $filters ?? [];
$queryParams = function(array $overrides = []) use ($filters, $page) {
    $params = array_merge([
        'search' => $filters['search'] ?? '',
        'action' => $filters['action'] ?? '',
        'user_id' => $filters['user_id'] ?? '',
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'page' => $page,
    ], $overrides);
    return '?' . http_build_query($params);
};
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
            <p class="mt-1 text-sm opacity-60">View and filter all system activity.</p>
        </div>
        <a href="<?= \App\Config\App::basePath() ?>/admin/audit-logs/export<?= $queryParams(['page' => null]) ?>" class="btn btn-outline btn-sm gap-2"><i data-lucide="download" class="h-4 w-4"></i> Export CSV</a>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="<?= \App\Config\App::basePath() ?>/admin/audit-logs" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search logs..." class="input input-bordered input-sm" />
                <select name="action" class="select select-bordered select-sm">
                    <option value="">All actions</option>
                    <?php foreach (($actions ?? []) as $action): ?>
                        <option value="<?= htmlspecialchars($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>><?= htmlspecialchars($action) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="user_id" value="<?= htmlspecialchars($filters['user_id'] ?? '') ?>" placeholder="User ID" class="input input-bordered input-sm" />
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>" class="input input-bordered input-sm" />
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>" class="input input-bordered input-sm" />
                <div class="md:col-span-5 flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Apply Filters</button>
                    <a href="<?= \App\Config\App::basePath() ?>/admin/audit-logs" class="btn btn-sm btn-ghost">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-12 opacity-50">No logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover">
                                <td class="text-sm opacity-60"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <div class="font-medium"><?= htmlspecialchars($log['user_name'] ?? 'System / Guest') ?></div>
                                    <?php if (!empty($log['user_email'])): ?><div class="text-xs opacity-50"><?= htmlspecialchars($log['user_email']) ?></div><?php endif; ?>
                                </td>
                                <td><div class="badge badge-info badge-sm"><?= htmlspecialchars($log['action']) ?></div></td>
                                <td class="text-sm opacity-60"><?= htmlspecialchars($log['entity_type'] ?? 'N/A') ?><?= !empty($log['entity_id']) ? ' (#' . (int)$log['entity_id'] . ')' : '' ?></td>
                                <td>
                                    <?php if ($log['details']): ?>
                                        <button onclick="document.getElementById('detail-<?= $log['id'] ?>').showModal()" class="btn btn-ghost btn-xs btn-primary">View</button>
                                        <dialog id="detail-<?= $log['id'] ?>" class="modal">
                                            <div class="modal-box max-h-[85vh] overflow-y-auto">
                                                <h3 class="font-bold text-lg mb-3">Log Details</h3>
                                                <pre class="bg-base-200 p-4 rounded-box text-xs overflow-auto max-h-96"><?= htmlspecialchars(json_encode(json_decode($log['details'], true), JSON_PRETTY_PRINT)) ?></pre>
                                                <div class="modal-action"><form method="dialog"><button class="btn btn-sm">Close</button></form></div>
                                            </div>
                                            <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                        </dialog>
                                    <?php else: ?>
                                        <span class="opacity-30">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($pages ?? 0) > 1): ?>
            <div class="flex items-center justify-between px-4 py-3 border-t border-base-200">
                <p class="text-sm opacity-60">Page <?= (int)$page ?> of <?= (int)$pages ?> (<?= (int)$total ?> total)</p>
                <div class="join">
                    <?php if ($page > 1): ?>
                        <a href="<?= $queryParams(['page' => $page - 1]) ?>" class="join-item btn btn-sm">Prev</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm btn-disabled">Prev</button>
                    <?php endif; ?>

                    <?php if ($page < $pages): ?>
                        <a href="<?= $queryParams(['page' => $page + 1]) ?>" class="join-item btn btn-sm">Next</a>
                    <?php else: ?>
                        <button class="join-item btn btn-sm btn-disabled">Next</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

