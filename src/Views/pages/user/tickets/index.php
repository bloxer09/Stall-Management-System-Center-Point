<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <button type="button" onclick="openTicketModal()" class="btn btn-primary btn-sm gap-2" <?= empty($mappings) ? 'disabled' : '' ?>>
            <i data-lucide="plus" class="h-4 w-4"></i> New Ticket
        </button>
    </div>
    <?php if (empty($mappings)): ?>
        <div class="alert alert-info shadow-sm">
            <i data-lucide="info" class="w-5 h-5"></i>
            <span>You need an active stall assignment to create a ticket.</span>
        </div>
    <?php endif; ?>
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= \App\Config\App::basePath() ?>/user/tickets" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search tickets..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/user/tickets" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>ID / Date</th>
                        <th>Stall</th>
                        <th>Issue</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr><td colspan="5" class="text-center py-12 opacity-50">No tickets found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover">
                                <td><div class="font-medium">#<?= $ticket['id'] ?></div><div class="text-xs opacity-50"><?= date('M d, Y', strtotime($ticket['created_at'])) ?></div></td>
                                <td><?= htmlspecialchars($ticket['stall_number']) ?></td>
                                <td class="max-w-xs truncate text-sm opacity-70"><?= htmlspecialchars(substr($ticket['issue'], 0, 60)) ?><?= strlen($ticket['issue']) > 60 ? '...' : '' ?></td>
                                <td>
                                    <?php
                                        $badge = 'badge-ghost';
                                        if ($ticket['status'] === 'open') $badge = 'badge-warning';
                                        if ($ticket['status'] === 'in_progress') $badge = 'badge-info';
                                        if ($ticket['status'] === 'resolved') $badge = 'badge-success';
                                    ?>
                                    <div class="badge <?= $badge ?> badge-sm"><?= ucfirst(str_replace('_', ' ', htmlspecialchars($ticket['status']))) ?></div>
                                </td>
                                <td class="text-right">
                                    <a href="<?= \App\Config\App::basePath() ?>/user/tickets/view/<?= $ticket['id'] ?>" class="btn btn-ghost btn-xs gap-1"><i data-lucide="eye" class="w-4 h-4"></i> View</a>
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

<!-- New Ticket Modal -->
<?php if (!empty($mappings)): ?>
<dialog id="ticketModal" class="modal">
    <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4">Submit New Ticket</h3>
        <form action="<?= \App\Config\App::basePath() ?>/user/tickets/create" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Select Stall <span class="text-error">*</span></span></label>
                <select name="stall_id" required class="select select-bordered w-full">
                    <option value="">Select a stall...</option>
                    <?php foreach ($mappings as $map): ?>
                        <option value="<?= $map['stall_id'] ?>">Stall <?= htmlspecialchars($map['stall_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Issue Description <span class="text-error">*</span></span></label>
                <textarea name="issue" rows="5" required class="textarea textarea-bordered" placeholder="Describe the maintenance issue or request..."></textarea>
            </div>
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('ticketModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
<?php endif; ?>

<script>
function openTicketModal() {
    const modal = document.getElementById('ticketModal');
    if (modal) modal.showModal();
}
</script>
