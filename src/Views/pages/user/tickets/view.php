<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/user/tickets" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 space-y-4">
            <!-- Issue Description -->
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base">Issue Description</h3>
                    <p class="whitespace-pre-wrap opacity-80"><?= htmlspecialchars($ticket['issue']) ?></p>
                </div>
            </div>

            <?php require __DIR__ . '/../../../components/ticket_conversation.php'; ?>
        </div>

        <!-- Ticket Details Sidebar -->
        <div class="space-y-4">
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-base mb-3">Details</h3>
                    <dl class="space-y-4">
                        <div><dt class="text-sm font-medium opacity-60">Stall</dt><dd class="mt-1 text-sm font-medium"><?= htmlspecialchars($ticket['stall_number']) ?></dd></div>
                        <div><dt class="text-sm font-medium opacity-60">Status</dt><dd class="mt-1">
                            <?php
                                $badge = 'badge-ghost';
                                if ($ticket['status'] === 'open') $badge = 'badge-warning';
                                if ($ticket['status'] === 'in_progress') $badge = 'badge-info';
                                if ($ticket['status'] === 'resolved') $badge = 'badge-success';
                            ?>
                            <div class="badge <?= $badge ?> badge-sm" data-ticket-status-badge><span data-ticket-status-label><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span></div>
                        </dd></div>
                        <div><dt class="text-sm font-medium opacity-60">Submitted</dt><dd class="mt-1 text-sm"><?= date('F j, Y, g:i a', strtotime($ticket['created_at'])) ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>




