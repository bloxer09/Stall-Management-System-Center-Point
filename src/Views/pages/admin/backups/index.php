<?php $appName = \App\Config\App::name(); ?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="database" class="w-7 h-7 text-primary"></i>
                Database Backups
            </h1>
            <p class="text-sm opacity-60">Create, manage, and download SQL database snapshot backups.</p>
        </div>
        <form action="<?= \App\Config\App::basePath() ?>/admin/settings/backups/create" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <button type="submit" class="btn btn-primary gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Create New Backup
            </button>
        </form>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-0">
            <?php if (empty($backups)): ?>
                <div class="text-center py-12 px-4">
                    <i data-lucide="hard-drive" class="w-12 h-12 mx-auto text-base-content/30 mb-3"></i>
                    <p class="text-lg font-semibold">No backups available</p>
                    <p class="text-sm opacity-60 mb-4">Click "Create New Backup" above to generate a SQL backup snapshot.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>File Size</th>
                                <th>Created Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr class="hover">
                                    <td class="font-mono text-xs font-semibold">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="file-text" class="w-4 h-4 text-primary"></i>
                                            <?= htmlspecialchars($backup['filename'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td class="text-xs"><?= round($backup['size'] / 1024, 2) ?> KB</td>
                                    <td class="text-xs opacity-70"><?= date('M d, Y h:i:s A', $backup['created']) ?></td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?= \App\Config\App::basePath() ?>/admin/settings/backups/download/<?= urlencode($backup['filename']) ?>" 
                                               class="btn btn-outline btn-xs gap-1">
                                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Download
                                            </a>
                                            <form action="<?= \App\Config\App::basePath() ?>/admin/settings/backups/delete/<?= urlencode($backup['filename']) ?>" 
                                                  method="POST" onsubmit="return confirm('Are you sure you want to delete this backup file?');">
                                                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                                <button type="submit" class="btn btn-ghost btn-xs text-error">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
