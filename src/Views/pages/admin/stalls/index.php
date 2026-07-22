<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <div class="flex gap-2">
            <a href="<?= \App\Config\App::basePath() ?>/admin/stalls/export?search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>" class="btn btn-outline btn-sm gap-2">
                <i data-lucide="download" class="h-4 w-4"></i> Export CSV
            </a>
            <button type="button" onclick="openStallModal('create')" class="btn btn-primary btn-sm gap-2">
                <i data-lucide="plus" class="h-4 w-4"></i> New Stall
            </button>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4 flex flex-col sm:flex-row justify-between gap-4">
            <form action="<?= \App\Config\App::basePath() ?>/admin/stalls" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search stalls..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="<?= \App\Config\App::basePath() ?>/admin/stalls" class="btn btn-sm btn-error btn-outline join-item">Clear</a>
                <?php endif; ?>
            </form>
            <form id="bulkActionForm" action="<?= \App\Config\App::basePath() ?>/admin/stalls/bulk" method="POST" class="join">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <input type="hidden" name="ids" id="bulkIds" value="">
                <select name="action" class="select select-bordered select-sm join-item">
                    <option value="">Bulk Actions</option>
                    <option value="available">Mark Available</option>
                    <option value="occupied">Mark Occupied</option>
                    <option value="maintenance">Mark Maintenance</option>
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
                        <th><a href="?sort=s.stall_number&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Stall Number <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Location</th>
                        <th>Image</th>
                        <th><a href="?sort=s.status&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=s.price&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Price <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Description</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stalls)): ?>
                        <tr><td colspan="8" class="text-center py-12 opacity-50">No stalls found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($stalls as $stall): ?>
                            <tr class="hover">
                                <th><input type="checkbox" value="<?= $stall['id'] ?>" class="checkbox checkbox-sm item-checkbox" /></th>
                                <td class="font-medium"><?= htmlspecialchars($stall['stall_number']) ?></td>
                                <td>
                                    <?php if (!empty($stall['location_name'])): ?>
                                        <div class="badge badge-info badge-outline badge-sm"><?= htmlspecialchars($stall['location_name']) ?></div>
                                    <?php else: ?>
                                        <span class="opacity-40">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($stall['image'])): ?>
                                        <?php $stallImageUrl = \App\Config\App::path('/' . ltrim($stall['image'], '/')); ?>
                                        <div class="avatar"><div class="w-12 h-12 rounded"><img src="<?= htmlspecialchars($stallImageUrl) ?>" alt="Stall" /></div></div>
                                    <?php else: ?>
                                        <div class="avatar placeholder"><div class="bg-base-300 text-base-content rounded w-12 h-12"><i data-lucide="store" class="w-5 h-5 opacity-30"></i></div></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $badgeClass = 'badge-ghost';
                                        if ($stall['status'] === 'available') $badgeClass = 'badge-success';
                                        if ($stall['status'] === 'occupied') $badgeClass = 'badge-info';
                                        if ($stall['status'] === 'maintenance') $badgeClass = 'badge-warning';
                                    ?>
                                    <div class="badge <?= $badgeClass ?> badge-sm"><?= ucfirst(htmlspecialchars($stall['status'])) ?></div>
                                </td>
                                <td>PHP <?= number_format((float)$stall['price'], 2) ?></td>
                                <td class="max-w-xs truncate text-sm opacity-60"><?= htmlspecialchars($stall['description'] ?? '') ?></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openStallModal('edit', <?= htmlspecialchars(json_encode([
                                            'id' => $stall['id'],
                                            'stall_number' => $stall['stall_number'],
                                            'price' => $stall['price'],
                                            'status' => $stall['status'],
                                            'description' => $stall['description'] ?? '',
                                            'location_id' => $stall['location_id'] ?? '',
                                            'image' => $stall['image'] ?? '',
                                        ])) ?>)">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <form action="<?= \App\Config\App::basePath() ?>/admin/stalls/delete/<?= $stall['id'] ?>" method="POST" class="inline" data-confirm-message="Delete this stall?" data-confirm-title="Delete Stall" data-confirm-confirm-label="Delete" data-confirm-variant="error">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                        <button type="submit" class="btn btn-ghost btn-xs text-error"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
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

<!-- Create / Edit Stall Modal -->
<dialog id="stallModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4" id="stallModalTitle">New Stall</h3>
        <form id="stallForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Stall Number <span class="text-error">*</span></span></label>
                    <input type="text" name="stall_number" id="m_stall_number" required class="input input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Price (Monthly) <span class="text-error">*</span></span></label>
                    <label class="input input-bordered flex items-center gap-2 w-full">
                        <span class="opacity-50">PHP</span>
                        <input type="number" step="0.01" name="price" id="m_price" required class="grow" />
                    </label>
                </div>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Location</span></label>
                <select id="m_location_id" name="location_id" class="select select-bordered w-full">
                    <option value="">Unassigned</option>
                    <?php foreach (($locations ?? []) as $location): ?>
                        <option value="<?= $location['id'] ?>"><?= htmlspecialchars($location['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Status</span></label>
                <select id="m_status" name="status" class="select select-bordered w-full">
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Description</span></label>
                <textarea id="m_description" name="description" rows="3" class="textarea textarea-bordered"></textarea>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Stall Image</span></label>
                <div id="m_current_image" class="hidden mb-2">
                    <img id="m_image_preview" src="" alt="Current" class="h-24 w-auto object-cover rounded-box border border-base-200" />
                    <label class="label cursor-pointer justify-start gap-2 mt-1">
                        <input type="checkbox" name="remove_image" value="1" class="checkbox checkbox-sm">
                        <span class="label-text">Remove current image</span>
                    </label>
                </div>
                <input type="file" name="image" accept="image/*" class="file-input file-input-bordered w-full" />
            </div>

            <div class="modal-action">
                <button type="button" onclick="document.getElementById('stallModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary" id="stallSubmitBtn">Create Stall</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
const basePath = '<?= \App\Config\App::basePath() ?>';

function openStallModal(mode, data = {}) {
    const modal = document.getElementById('stallModal');
    const form = document.getElementById('stallForm');
    const title = document.getElementById('stallModalTitle');
    const submitBtn = document.getElementById('stallSubmitBtn');

    // Reset form
    form.reset();
    document.getElementById('m_current_image').classList.add('hidden');

    if (mode === 'edit' && data.id) {
        title.textContent = 'Edit Stall - ' + data.stall_number;
        submitBtn.textContent = 'Update Stall';
        form.action = basePath + '/admin/stalls/edit/' + data.id;
        document.getElementById('m_stall_number').value = data.stall_number || '';
        document.getElementById('m_price').value = data.price || '';
        document.getElementById('m_location_id').value = data.location_id || '';
        document.getElementById('m_status').value = data.status || 'available';
        document.getElementById('m_description').value = data.description || '';
        if (data.image) {
            document.getElementById('m_current_image').classList.remove('hidden');
            document.getElementById('m_image_preview').src = basePath + '/' + data.image.replace(/^\//, '');
        }
    } else {
        title.textContent = 'New Stall';
        submitBtn.textContent = 'Create Stall';
        form.action = basePath + '/admin/stalls/create';
    }

    modal.showModal();
}

// Bulk & Select All
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
});
async function submitBulkAction() {
    const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        await window.appDialog.alert('Select at least one stall to continue.', { title: 'Nothing selected', variant: 'warning' });
        return;
    }
    const actionSelect = document.querySelector('select[name="action"]');
    const action = actionSelect.value;
    if (!action) {
        await window.appDialog.alert('Choose a bulk action first.', { title: 'Action required', variant: 'warning' });
        return;
    }
    const confirmed = await window.appDialog.confirm(
        `Perform "${actionSelect.options[actionSelect.selectedIndex].text}" on ${selected.length} stall(s)?`,
        { title: 'Confirm bulk action', confirmLabel: 'Apply', variant: action === 'delete' ? 'error' : 'primary' }
    );
    if (!confirmed) return;
    document.getElementById('bulkIds').value = selected.join(',');
    document.getElementById('bulkActionForm').submit();
}
</script>
