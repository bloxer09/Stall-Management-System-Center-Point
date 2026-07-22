<?php
$userId = \App\Core\Auth::isLoggedIn() ? \App\Core\Auth::user()['id'] : null;
$unreadCount = 0;
$recentNotifications = [];
if ($userId) {
    $notifModel = new \App\Models\Notification();
    $unreadCount = $notifModel->getUnreadCount($userId);
    $recentNotifications = $notifModel->getForUser($userId, 5);
}
?>
<div class="dropdown dropdown-end" id="notificationDropdown">
    <div tabindex="0" role="button" class="btn btn-ghost btn-circle btn-sm relative">
        <i data-lucide="bell" class="w-5 h-5"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="badge badge-xs badge-primary absolute top-1 right-1 font-bold animate-pulse"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
        <?php endif; ?>
    </div>
    <div tabindex="0" class="dropdown-content z-[100] card card-compact w-80 bg-base-100 shadow-2xl border border-base-200 mt-3">
        <div class="card-body p-3">
            <div class="flex items-center justify-between border-b border-base-200 pb-2">
                <span class="font-bold text-sm flex items-center gap-1.5">
                    <i data-lucide="bell" class="w-4 h-4 text-primary"></i> Notifications
                </span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge badge-primary badge-sm font-semibold"><?= $unreadCount ?> unread</span>
                <?php endif; ?>
            </div>
            
            <div class="max-h-64 overflow-y-auto divide-y divide-base-200 my-1">
                <?php if (empty($recentNotifications)): ?>
                    <div class="text-center py-6 text-xs opacity-50">No notifications</div>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notif): ?>
                        <a href="<?= \App\Config\App::basePath() ?><?= !empty($notif['link']) ? htmlspecialchars($notif['link'], ENT_QUOTES, 'UTF-8') : '/notifications' ?>" 
                           class="block p-2 hover:bg-base-200 text-xs rounded-lg transition-colors <?= $notif['is_read'] ? 'opacity-60' : 'bg-primary/5 font-medium' ?>">
                            <div class="font-semibold text-sm truncate"><?= htmlspecialchars($notif['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-base-content/70 line-clamp-2 mt-0.5"><?= htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-[10px] opacity-40 mt-1"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="border-t border-base-200 pt-2 flex items-center justify-between text-xs">
                <a href="<?= \App\Config\App::basePath() ?>/notifications" class="link link-primary font-medium">View all</a>
                <?php if ($unreadCount > 0): ?>
                    <form action="<?= \App\Config\App::basePath() ?>/notifications/read-all" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                        <button type="submit" class="link link-hover text-opacity-60">Mark all read</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
