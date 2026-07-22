<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <button type="button" onclick="openLocationModal('create')" class="btn btn-primary btn-sm gap-2">
            <i data-lucide="plus" class="h-4 w-4"></i> New Location
        </button>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Layout Grid Size</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($locations)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-12 opacity-50">No locations yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($location['name']) ?></td>
                                <td class="opacity-70"><?= (int)($location['grid_rows'] ?? 6) ?> x <?= (int)($location['grid_cols'] ?? 8) ?></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="openLocationModal('edit', <?= htmlspecialchars(json_encode([
                                        'id' => $location['id'],
                                        'name' => $location['name'],
                                        'description' => $location['description'] ?? '',
                                        'sort_order' => $location['sort_order'] ?? 0,
                                        'grid_rows' => $location['grid_rows'] ?? 6,
                                        'grid_cols' => $location['grid_cols'] ?? 8,
                                    ])) ?>)">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <form
                                        action="<?= \App\Config\App::basePath() ?>/admin/stall-locations/delete/<?= $location['id'] ?>"
                                        method="POST" class="inline" data-confirm-message="Delete this location?"
                                        data-confirm-title="Delete Location" data-confirm-confirm-label="Delete"
                                        data-confirm-variant="error">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                        <button type="submit" class="btn btn-ghost btn-xs text-error">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create / Edit Location Modal -->
<dialog id="locationModal" class="modal">
    <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4" id="locationModalTitle">New Location</h3>
        <form id="locationForm" action="" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Location Name <span
                            class="text-error">*</span></span></label>
                <input type="text" id="ml_name" name="name" required class="input input-bordered" />
            </div>

            <!-- <div class="form-control">
                <label class="label"><span class="label-text font-medium">Description</span></label>
                <textarea id="ml_description" name="description" rows="3" class="textarea textarea-bordered"></textarea>
            </div> -->

            <!-- <div class="form-control">
                <label class="label"><span class="label-text font-medium">Sort Order</span></label>
                <input type="number" id="ml_sort_order" name="sort_order" value="0" class="input input-bordered" />
            </div> -->

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Grid Rows</span></label>
                    <input type="number" id="ml_grid_rows" name="grid_rows" min="1" max="50" required class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Grid Columns</span></label>
                    <input type="number" id="ml_grid_cols" name="grid_cols" min="1" max="50" required class="input input-bordered" />
                </div>
            </div>

            <div class="modal-action">
                <button type="button" onclick="document.getElementById('locationModal').close()"
                    class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary" id="locationSubmitBtn">Create Location</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const basePath = '<?= \App\Config\App::basePath() ?>';

    function openLocationModal(mode, data = {}) {
        const modal = document.getElementById('locationModal');
        const form = document.getElementById('locationForm');
        const title = document.getElementById('locationModalTitle');
        const submitBtn = document.getElementById('locationSubmitBtn');

        form.reset();

        if (mode === 'edit' && data.id) {
            title.textContent = 'Edit Location';
            submitBtn.textContent = 'Update Location';
            form.action = basePath + '/admin/stall-locations/edit/' + data.id;
            document.getElementById('ml_name').value = data.name || '';
            document.getElementById('ml_description').value = data.description || '';
            document.getElementById('ml_sort_order').value = data.sort_order || 0;
            document.getElementById('ml_grid_rows').value = data.grid_rows || 6;
            document.getElementById('ml_grid_cols').value = data.grid_cols || 8;
        } else {
            title.textContent = 'New Location';
            submitBtn.textContent = 'Create Location';
            form.action = basePath + '/admin/stall-locations/create';
            document.getElementById('ml_grid_rows').value = 6;
            document.getElementById('ml_grid_cols').value = 8;
        }

        modal.showModal();
    }
</script>