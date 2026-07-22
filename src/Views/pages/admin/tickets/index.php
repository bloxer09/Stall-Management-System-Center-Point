<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4 flex flex-col sm:flex-row justify-between gap-4">
            <form action="<?= \App\Config\App::basePath() ?>/admin/tickets" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search tickets..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/admin/tickets" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
            <form id="bulkActionForm" action="<?= \App\Config\App::basePath() ?>/admin/tickets/bulk" method="POST" class="join">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <input type="hidden" name="ids" id="bulkIds" value="">
                <select name="action" class="select select-bordered select-sm join-item">
                    <option value="">Bulk Actions</option>
                    <option value="open">Mark Open</option>
                    <option value="in_progress">Mark In Progress</option>
                    <option value="resolved">Mark Resolved</option>
                    <option value="closed">Mark Closed</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="button" onclick="submitBulkAction()" class="btn btn-sm join-item">Apply</button>
            </form>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="checkbox checkbox-sm" /></th>
                        <th><a href="?sort=created_at&order=<?= ($sortColumn ?? 'created_at') === 'created_at' && ($sortOrder ?? 'DESC') === 'DESC' ? 'ASC' : 'DESC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">ID / Date <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=user_name&order=<?= ($sortColumn ?? 'created_at') === 'user_name' && ($sortOrder ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Tenant <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=stall_number&order=<?= ($sortColumn ?? 'created_at') === 'stall_number' && ($sortOrder ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Stall <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=status&order=<?= ($sortColumn ?? 'created_at') === 'status' && ($sortOrder ?? 'DESC') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="6" class="text-center py-12 opacity-50">No tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover">
                                <th><input type="checkbox" value="<?= $ticket['id'] ?>" class="checkbox checkbox-sm item-checkbox" /></th>
                                <td><div class="font-medium">#<?= $ticket['id'] ?></div><div class="text-xs opacity-50"><?= date('M d, Y H:i', strtotime($ticket['created_at'])) ?></div></td>
                                <td class="font-medium"><?= htmlspecialchars($ticket['user_name']) ?></td>
                                <td><?= htmlspecialchars($ticket['stall_number']) ?></td>
                                <td>
                                    <?php
                                        $badge = 'badge-ghost';
                                        if ($ticket['status'] === 'open') $badge = 'badge-warning';
                                        if ($ticket['status'] === 'in_progress') $badge = 'badge-info';
                                        if ($ticket['status'] === 'resolved') $badge = 'badge-success';
                                        if ($ticket['status'] === 'closed') $badge = 'badge-ghost';
                                    ?>
                                    <div class="badge <?= $badge ?> badge-sm"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($ticket['status']))) ?></div>
                                </td>
                                <td class="text-right">
                                    <a href="<?= \App\Config\App::basePath() ?>/admin/tickets/view/<?= $ticket['id'] ?>" class="btn btn-ghost btn-xs"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <form action="<?= \App\Config\App::basePath() ?>/admin/tickets/delete/<?= $ticket['id'] ?>" method="POST" class="inline" data-confirm-message="Delete this ticket?" data-confirm-title="Delete Ticket" data-confirm-confirm-label="Delete" data-confirm-variant="error"><input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>"><button type="submit" class="btn btn-ghost btn-xs text-error"><i data-lucide="trash-2" class="w-4 h-4"></i></button></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php require_once __DIR__ . '/../../../components/pagination.php'; ?>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function() { document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked); });
async function submitBulkAction() {
    const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        await window.appDialog.alert('Select at least one ticket to continue.', { title: 'Nothing selected', variant: 'warning' });
        return;
    }

    const actionSelect = document.querySelector('select[name="action"]');
    const action = actionSelect.value;
    if (!action) {
        await window.appDialog.alert('Choose a bulk action first.', { title: 'Action required', variant: 'warning' });
        return;
    }

    const confirmed = await window.appDialog.confirm(
        `Perform "${actionSelect.options[actionSelect.selectedIndex].text}" on ${selected.length} ticket(s)?`,
        {
            title: 'Confirm bulk action',
            confirmLabel: 'Apply',
            variant: action === 'delete' ? 'error' : 'primary'
        }
    );

    if (!confirmed) {
        return;
    }

    document.getElementById('bulkIds').value = selected.join(',');
    document.getElementById('bulkActionForm').submit();
}
</script>

