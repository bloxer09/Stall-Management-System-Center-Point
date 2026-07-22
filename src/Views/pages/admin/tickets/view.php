<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="<?= \App\Config\App::basePath() ?>/admin/tickets" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        </div>
        <form action="<?= \App\Config\App::basePath() ?>/admin/tickets/delete/<?= $ticket['id'] ?>" method="POST" data-confirm-message="Delete this ticket?" data-confirm-title="Delete Ticket" data-confirm-confirm-label="Delete" data-confirm-variant="error">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <button type="submit" class="btn btn-error btn-sm btn-outline gap-1"><i data-lucide="trash-2" class="h-4 w-4"></i> Delete</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Main Content -->
        <div class="md:col-span-2 space-y-4">
            <!-- Issue Description -->
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base">Issue Description</h3>
                    <p class="whitespace-pre-wrap opacity-80"><?= htmlspecialchars($ticket['issue']) ?></p>
                </div>
            </div>

            <!-- Conversation Thread â€” DaisyUI Chat Bubbles -->
            <?php require __DIR__ . '/../../../components/ticket_conversation.php'; ?>
        </div>

        <!-- Ticket Meta Sidebar -->
        <div class="space-y-4">
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base mb-3">Details</h3>
                    <dl class="space-y-4">
                        <div><dt class="text-sm font-medium opacity-60">Tenant</dt><dd class="mt-1 text-sm font-medium"><?= htmlspecialchars($ticket['user_name']) ?></dd></div>
                        <div><dt class="text-sm font-medium opacity-60">Stall Number</dt><dd class="mt-1 text-sm font-medium">Stall <?= htmlspecialchars($ticket['stall_number']) ?></dd></div>
                        <div><dt class="text-sm font-medium opacity-60">Status</dt><dd class="mt-1">
                            <?php
                                $badge = 'badge-ghost';
                                if ($ticket['status'] === 'open') $badge = 'badge-warning';
                                if ($ticket['status'] === 'in_progress') $badge = 'badge-info';
                                if ($ticket['status'] === 'resolved') $badge = 'badge-success';
                            ?>
                            <div class="badge <?= $badge ?> badge-sm" data-ticket-status-badge><span data-ticket-status-label><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></div>
                        </dd></div>
                        <div><dt class="text-sm font-medium opacity-60">Reported On</dt><dd class="mt-1 text-sm"><?= date('F j, Y, g:i a', strtotime($ticket['created_at'])) ?></dd></div>
                    </dl>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base mb-3">Update Status</h3>
                    <form action="<?= \App\Config\App::basePath() ?>/admin/tickets/update/<?= $ticket['id'] ?>" method="POST" class="space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                        <select name="status" class="select select-bordered w-full">
                            <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm w-full">Save Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>




