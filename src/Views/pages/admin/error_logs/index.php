<?php
$filters = $filters ?? [];
$availableFiles = $availableFiles ?? [];
$availableLevels = $availableLevels ?? [];
$page = $page ?? 1;
$pages = $pages ?? 0;
$total = $total ?? 0;
$returnQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');
$currentView = $filters['view'] ?? 'grouped';
$canManageLogs = \App\Core\Auth::isAdmin();
$resultLabel = $currentView === 'raw' ? 'Entries' : 'Issues';
$selectionLabel = $currentView === 'raw' ? 'entry' : 'issue';
$columnCount = ($canManageLogs ? 1 : 0) + 7 + ($canManageLogs ? 1 : 0);

$queryParams = function (array $overrides = []) use ($filters, $page) {
    $params = array_merge([
        'search' => $filters['search'] ?? '',
        'file' => $filters['file'] ?? '',
        'level' => $filters['level'] ?? '',
        'view' => $filters['view'] ?? 'grouped',
        'sort' => $filters['sort'] ?? 'timestamp',
        'order' => $filters['order'] ?? 'DESC',
        'page' => $page,
    ], $overrides);

    return '?' . http_build_query($params);
};

$levelBadgeClasses = [
    'fatal' => 'badge-error',
    'exception' => 'badge-error',
    'error' => 'badge-error',
    'warning' => 'badge-warning',
    'notice' => 'badge-info',
    'deprecated' => 'badge-secondary',
    'trace' => 'badge-ghost',
    'unknown' => 'badge-ghost',
];
?>

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
            <p class="mt-1 text-sm opacity-60">Review application log errors stored in <code>storage/logs</code>. Grouped view deduplicates repeating errors by source and message.</p>
            <?php if (!$canManageLogs): ?>
                <p class="mt-2 text-xs opacity-50">Staff can review logs, but only admins can delete entries or clear files.</p>
            <?php endif; ?>
        </div>
        <?php if ($canManageLogs): ?>
            <div class="flex flex-wrap gap-2">
                <form id="bulkDeleteErrorLogsForm" action="<?= \App\Config\App::basePath() ?>/admin/error-logs/delete" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <input type="hidden" name="ids" id="bulkErrorLogIds" value="">
                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" onclick="submitBulkErrorLogDelete()" class="btn btn-outline btn-sm gap-2">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Delete Selected
                    </button>
                </form>
                <form
                    action="<?= \App\Config\App::basePath() ?>/admin/error-logs/clear"
                    method="POST"
                    class="inline"
                    data-confirm-message="Clear every error entry from all application log files?"
                    data-confirm-title="Clear Error Logs"
                    data-confirm-confirm-label="Clear Logs"
                    data-confirm-variant="error"
                >
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-error btn-sm gap-2">
                        <i data-lucide="eraser" class="h-4 w-4"></i>
                        Clear All Logs
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="stat-title">Matching <?= htmlspecialchars($resultLabel) ?></div>
            <div class="stat-value text-primary"><?= number_format((int) $total) ?></div>
            <div class="stat-desc"><?= $currentView === 'raw' ? 'Raw log entries after filters' : 'Deduplicated issues after filters' ?></div>
        </div>
        <div class="stat bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="stat-title">Log Files</div>
            <div class="stat-value text-info"><?= number_format(count($availableFiles)) ?></div>
            <div class="stat-desc">Files detected in storage/logs</div>
        </div>
        <div class="stat bg-base-100 rounded-box shadow-sm border border-base-200">
            <div class="stat-title">Severity Types</div>
            <div class="stat-value text-warning"><?= number_format(count($availableLevels)) ?></div>
            <div class="stat-desc">Unique levels found</div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="<?= \App\Config\App::basePath() ?>/admin/error-logs" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <label class="input input-bordered input-sm flex items-center gap-2 md:col-span-2">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="Search messages, traces, files, reference IDs..." class="grow" />
                </label>
                <select name="file" class="select select-bordered select-sm">
                    <option value="">All log files</option>
                    <?php foreach ($availableFiles as $file): ?>
                        <option value="<?= htmlspecialchars($file) ?>" <?= ($filters['file'] ?? '') === $file ? 'selected' : '' ?>><?= htmlspecialchars($file) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="level" class="select select-bordered select-sm">
                    <option value="">All severity levels</option>
                    <?php foreach ($availableLevels as $level): ?>
                        <option value="<?= htmlspecialchars($level) ?>" <?= ($filters['level'] ?? '') === $level ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($level)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="view" class="select select-bordered select-sm">
                    <option value="grouped" <?= $currentView === 'grouped' ? 'selected' : '' ?>>Grouped issues</option>
                    <option value="raw" <?= $currentView === 'raw' ? 'selected' : '' ?>>Raw entries</option>
                </select>
                <div class="grid grid-cols-2 gap-3">
                    <select name="sort" class="select select-bordered select-sm">
                        <option value="timestamp" <?= ($filters['sort'] ?? '') === 'timestamp' ? 'selected' : '' ?>>Sort by time</option>
                        <option value="count" <?= ($filters['sort'] ?? '') === 'count' ? 'selected' : '' ?>>Sort by count</option>
                        <option value="level" <?= ($filters['sort'] ?? '') === 'level' ? 'selected' : '' ?>>Sort by severity</option>
                        <option value="file" <?= ($filters['sort'] ?? '') === 'file' ? 'selected' : '' ?>>Sort by file</option>
                        <option value="message" <?= ($filters['sort'] ?? '') === 'message' ? 'selected' : '' ?>>Sort by message</option>
                    </select>
                    <select name="order" class="select select-bordered select-sm">
                        <option value="DESC" <?= ($filters['order'] ?? '') === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        <option value="ASC" <?= ($filters['order'] ?? '') === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                    </select>
                </div>
                <div class="md:col-span-6 flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    <a href="<?= \App\Config\App::basePath() ?>/admin/error-logs" class="btn btn-ghost btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-base-200 text-sm opacity-70">
            Showing <?= number_format(count($logs ?? [])) ?> <?= strtolower($resultLabel) ?> on this page.
        </div>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <?php if ($canManageLogs): ?>
                            <th><input type="checkbox" id="selectAllErrorLogs" class="checkbox checkbox-sm" /></th>
                        <?php endif; ?>
                        <th>Last Seen</th>
                        <th>Severity</th>
                        <th>Log File</th>
                        <th><?= $currentView === 'raw' ? 'Message' : 'Issue' ?></th>
                        <th>Reference</th>
                        <th>Seen</th>
                        <th>Details</th>
                        <?php if ($canManageLogs): ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="<?= $columnCount ?>" class="text-center py-12 opacity-50">No error logs found for the current filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php $deleteIds = implode(',', $log['entry_ids'] ?? [$log['id']]); ?>
                            <tr class="hover align-top">
                                <?php if ($canManageLogs): ?>
                                    <td>
                                        <input type="checkbox" value="<?= htmlspecialchars($deleteIds) ?>" class="checkbox checkbox-sm error-log-checkbox" />
                                    </td>
                                <?php endif; ?>
                                <td class="text-sm whitespace-nowrap">
                                    <div class="font-medium">
                                        <?= htmlspecialchars(!empty($log['last_seen_timestamp']) ? date('Y-m-d H:i:s', strtotime($log['last_seen_timestamp'])) : $log['last_seen_display']) ?>
                                    </div>
                                    <div class="text-xs opacity-50"><?= htmlspecialchars($log['last_seen_display']) ?></div>
                                    <?php if (($log['occurrence_count'] ?? 1) > 1): ?>
                                        <div class="text-xs opacity-50 mt-1">First: <?= htmlspecialchars($log['first_seen_display']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="badge badge-sm <?= $levelBadgeClasses[$log['level']] ?? 'badge-ghost' ?>">
                                        <?= htmlspecialchars(ucfirst($log['level'])) ?>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    <div class="font-medium"><?= htmlspecialchars($log['file']) ?></div>
                                    <div class="text-xs opacity-50"><?= htmlspecialchars($log['source'] !== '' ? basename($log['source']) : 'No source detected') ?><?= $log['line'] !== null ? ':' . (int) $log['line'] : '' ?></div>
                                </td>
                                <td class="max-w-xl">
                                    <div class="font-medium leading-6"><?= htmlspecialchars($log['summary']) ?></div>
                                    <?php if ($log['source'] !== ''): ?>
                                        <div class="text-xs opacity-50 mt-1" title="<?= htmlspecialchars($log['source']) ?>">Source: <?= htmlspecialchars($log['source']) ?></div>
                                    <?php endif; ?>
                                    <?php if (($log['occurrence_count'] ?? 1) > 1): ?>
                                        <div class="text-xs opacity-60 mt-1"><?= (int) $log['occurrence_count'] ?> matching occurrences grouped together</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <?php if (!empty($log['latest_reference_id'])): ?>
                                        <div class="font-mono text-xs"><?= htmlspecialchars($log['latest_reference_id']) ?></div>
                                        <?php if (count($log['reference_ids'] ?? []) > 1): ?>
                                            <div class="text-xs opacity-50 mt-1"><?= number_format(count($log['reference_ids'])) ?> unique reference IDs</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="opacity-40">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm">
                                    <div class="font-medium"><?= number_format((int) ($log['occurrence_count'] ?? 1)) ?></div>
                                    <div class="text-xs opacity-50"><?= (int) ($log['occurrence_count'] ?? 1) === 1 ? 'Occurrence' : 'Occurrences' ?></div>
                                </td>
                                <td>
                                    <button type="button" onclick="document.getElementById('error-log-detail-<?= $log['id'] ?>').showModal()" class="btn btn-ghost btn-xs btn-primary">
                                        View
                                    </button>
                                    <dialog id="error-log-detail-<?= $log['id'] ?>" class="modal">
                                        <div class="modal-box max-w-5xl max-h-[85vh] overflow-y-auto">
                                            <h3 class="font-bold text-lg mb-3"><?= $currentView === 'raw' ? 'Error Log Entry' : 'Error Issue Details' ?></h3>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 text-sm">
                                                <div class="rounded-box bg-base-200 px-3 py-2">
                                                    <div class="opacity-50 text-xs uppercase tracking-wide">Last Seen</div>
                                                    <div class="font-medium mt-1"><?= htmlspecialchars($log['last_seen_display']) ?></div>
                                                </div>
                                                <div class="rounded-box bg-base-200 px-3 py-2">
                                                    <div class="opacity-50 text-xs uppercase tracking-wide">First Seen</div>
                                                    <div class="font-medium mt-1"><?= htmlspecialchars($log['first_seen_display']) ?></div>
                                                </div>
                                                <div class="rounded-box bg-base-200 px-3 py-2">
                                                    <div class="opacity-50 text-xs uppercase tracking-wide">Occurrences</div>
                                                    <div class="font-medium mt-1"><?= number_format((int) ($log['occurrence_count'] ?? 1)) ?></div>
                                                </div>
                                                <div class="rounded-box bg-base-200 px-3 py-2">
                                                    <div class="opacity-50 text-xs uppercase tracking-wide">Latest Reference ID</div>
                                                    <div class="font-medium mt-1 font-mono"><?= htmlspecialchars($log['latest_reference_id'] ?: 'N/A') ?></div>
                                                </div>
                                            </div>
                                            <div class="space-y-3 max-h-[28rem] overflow-auto pr-1">
                                                <?php foreach (($log['occurrences'] ?? []) as $occurrence): ?>
                                                    <div class="rounded-box border border-base-200 bg-base-200/60 p-4">
                                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3 text-sm">
                                                            <div>
                                                                <span class="font-medium">Seen:</span>
                                                                <?= htmlspecialchars($occurrence['timestamp_display'] ?: 'Unknown') ?>
                                                            </div>
                                                            <div class="font-mono text-xs opacity-70">
                                                                <?= htmlspecialchars($occurrence['reference_id'] ?: 'No reference ID') ?>
                                                            </div>
                                                        </div>
                                                        <pre class="bg-base-100 p-4 rounded-box text-xs overflow-auto whitespace-pre-wrap break-words"><?= htmlspecialchars($occurrence['details']) ?></pre>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="modal-action">
                                                <form method="dialog">
                                                    <button class="btn btn-sm">Close</button>
                                                </form>
                                            </div>
                                        </div>
                                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                                    </dialog>
                                </td>
                                <?php if ($canManageLogs): ?>
                                    <td class="text-right whitespace-nowrap">
                                        <form
                                            action="<?= \App\Config\App::basePath() ?>/admin/error-logs/delete"
                                            method="POST"
                                            class="inline"
                                            data-confirm-message="Delete this <?= htmlspecialchars($selectionLabel, ENT_QUOTES, 'UTF-8') ?> from the log files?"
                                            data-confirm-title="Delete Error Log"
                                            data-confirm-confirm-label="Delete"
                                            data-confirm-variant="error"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                            <input type="hidden" name="ids" value="<?= htmlspecialchars($deleteIds) ?>">
                                            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" class="btn btn-ghost btn-xs text-error">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="flex items-center justify-between px-4 py-3 border-t border-base-200">
                <p class="text-sm opacity-60">Page <?= (int) $page ?> of <?= (int) $pages ?> (<?= (int) $total ?> total)</p>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAllErrorLogs');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.error-log-checkbox').forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }
});

async function submitBulkErrorLogDelete() {
    var selected = Array.from(document.querySelectorAll('.error-log-checkbox:checked')).map(function (checkbox) {
        return checkbox.value;
    });

    if (!selected.length) {
        await window.appDialog.alert('Select at least one error <?= strtolower($selectionLabel) ?> to continue.', {
            title: 'Nothing selected',
            variant: 'warning'
        });
        return;
    }

    var confirmed = await window.appDialog.confirm(
        'Delete ' + selected.length + ' selected error <?= strtolower($selectionLabel) ?>' + (selected.length === 1 ? '' : 's') + '?',
        {
            title: 'Delete Error Logs',
            confirmLabel: 'Delete',
            variant: 'error'
        }
    );

    if (!confirmed) {
        return;
    }

    document.getElementById('bulkErrorLogIds').value = selected.join(',');
    document.getElementById('bulkDeleteErrorLogsForm').submit();
}
</script>
