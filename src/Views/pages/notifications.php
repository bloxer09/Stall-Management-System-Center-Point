<?php $appName = \App\Config\App::name(); ?>
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="bell" class="w-6 h-6 text-primary"></i>
                Notification Center
            </h1>
            <p class="text-sm opacity-60">View your latest system alerts, payment updates, and reminders.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <form action="<?= \App\Config\App::basePath() ?>/notifications/read-all" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <button type="submit" class="btn btn-outline btn-sm gap-2">
                    <i data-lucide="check-check" class="w-4 h-4"></i> Mark all as read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-12 px-4">
                    <i data-lucide="bell-off" class="w-12 h-12 mx-auto text-base-content/30 mb-3"></i>
                    <p class="text-lg font-semibold">No notifications</p>
                    <p class="text-sm opacity-60">You are all caught up! Updates will appear here.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-base-200">
                    <?php foreach ($notifications as $item): ?>
                        <div class="p-4 hover:bg-base-200/50 transition-colors flex items-start justify-between gap-4 <?= $item['is_read'] ? 'opacity-70' : 'bg-primary/5 font-medium' ?>">
                            <div class="flex items-start gap-3">
                                <div class="mt-1">
                                    <?php if ($item['type'] === 'success'): ?>
                                        <i data-lucide="check-circle" class="w-5 h-5 text-success"></i>
                                    <?php elseif ($item['type'] === 'warning'): ?>
                                        <i data-lucide="alert-triangle" class="w-5 h-5 text-warning"></i>
                                    <?php elseif ($item['type'] === 'danger'): ?>
                                        <i data-lucide="alert-circle" class="w-5 h-5 text-error"></i>
                                    <?php else: ?>
                                        <i data-lucide="info" class="w-5 h-5 text-info"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="text-sm text-base-content/80 mt-0.5"><?= htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <span class="text-xs opacity-50 block mt-1"><?= date('M d, Y h:i A', strtotime($item['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <?php if (!empty($item['link'])): ?>
                                    <a href="<?= htmlspecialchars(\App\Config\App::basePath() . $item['link'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost btn-xs">View</a>
                                <?php endif; ?>
                                <?php if (!$item['is_read']): ?>
                                    <form action="<?= \App\Config\App::basePath() ?>/notifications/mark-read/<?= $item['id'] ?>" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                        <button type="submit" class="btn btn-circle btn-ghost btn-xs" title="Mark as read">
                                            <i data-lucide="check" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
