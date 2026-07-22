<?php use App\Services\RegistrationVerification; ?>
<link href="<?= \App\Config\App::basePath() ?>/public/assets/css/tom-select.min.css" rel="stylesheet">

<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <button type="button" onclick="openMappingModal('create')" class="btn btn-primary btn-sm gap-2"><i data-lucide="plus-circle" class="h-4 w-4"></i> Assign Stall</button>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= \App\Config\App::basePath() ?>/admin/mappings" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search by user, stall, or location..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/admin/mappings" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th><a href="?sort=s.stall_number&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Stall <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Location</th>
                        <th><a href="?sort=u.name&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Tenant <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=m.start_date&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Duration <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=m.is_active&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Monthly Fee</th>
                        <!-- <th>Actions</th> -->
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mappings)): ?>
                        <tr><td colspan="7" class="text-center py-12 opacity-50">No assignments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mappings as $map): ?>
                            <tr class="hover">
                                <td class="font-medium"><i data-lucide="store" class="w-4 h-4 inline mr-1 opacity-40"></i><?= htmlspecialchars($map['stall_number']) ?></td>
                                <td>
                                    <?php if (!empty($map['location_name'])): ?>
                                        <div class="badge badge-info badge-outline badge-sm"><?= htmlspecialchars($map['location_name']) ?></div>
                                    <?php else: ?>
                                        <span class="opacity-40">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder"><div class="bg-primary text-primary-content rounded-full w-6"><span class="text-xs"><?= strtoupper(substr($map['user_name'],0,1)) ?></span></div></div>
                                        <?= htmlspecialchars($map['user_name']) ?>
                                    </div>
                                </td>
                                <td class="text-sm">
                                    <span><?= date('M d, Y', strtotime($map['start_date'])) ?></span><br>
                                    <span class="text-xs opacity-50">to <?= date('M d, Y', strtotime($map['end_date'])) ?></span>
                                </td>
                                <td><div class="badge <?= $map['is_active'] ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $map['is_active'] ? 'Active' : 'Inactive' ?></div></td>
                                <td>PHP <?= number_format((float)$map['price'], 2) ?></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-xs"
                                        onclick="openMappingModal('edit', <?= htmlspecialchars(json_encode([
                                            'id' => $map['id'],
                                            'user_id' => $map['user_id'],
                                            'stall_id' => $map['stall_id'],
                                            'stall_number' => $map['stall_number'],
                                            'start_date' => $map['start_date'],
                                            'end_date' => $map['end_date'],
                                            'is_active' => $map['is_active'],
                                            'contract_document' => $map['contract_document'] ?? '',
                                        ])) ?>)">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <!-- <form action="<?= \App\Config\App::basePath() ?>/admin/mappings/renew/<?= $map['id'] ?>" method="POST" class="inline" data-confirm-message="Renew lease for 12 months?" data-confirm-title="Renew Lease" data-confirm-confirm-label="Renew" data-confirm-variant="success">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                                        <button type="submit" class="btn btn-ghost btn-xs text-success" title="Renew 12 months"><i data-lucide="calendar-plus" class="w-4 h-4"></i></button>
                                    </form> -->
                                    <form action="<?= \App\Config\App::basePath() ?>/admin/mappings/delete/<?= $map['id'] ?>" method="POST" class="inline" data-confirm-message="Unassign this stall?" data-confirm-title="Unassign Stall" data-confirm-confirm-label="Unassign" data-confirm-variant="error">
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

<!-- Assign Stall Modal -->
<dialog id="mappingModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4" id="mappingModalTitle">Assign Stall</h3>
        <form id="mappingForm" action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Tenant (User) <span class="text-error">*</span></span></label>
                <select id="mm_user_id" name="user_id" required>
                    <option value="">Select a user...</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?><?php if (!empty($user['phone'])): ?> • <?= htmlspecialchars(RegistrationVerification::formatPhoneForDisplay((string) $user['phone'])) ?><?php endif; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Stall <span class="text-error">*</span></span></label>
                <select id="mm_stall_id" name="stall_id" required>
                    <option value="">Select a stall...</option>
                    <?php foreach ($stalls as $stall): ?>
                        <option value="<?= $stall['id'] ?>" data-status="<?= htmlspecialchars($stall['status']) ?>">
                            <?= htmlspecialchars($stall['stall_number']) ?>
                            <?php if (!empty($stall['location_name'])): ?> - <?= htmlspecialchars($stall['location_name']) ?><?php endif; ?>
                            - PHP <?= number_format((float)$stall['price'], 2) ?>/month
                            (<?= ucfirst($stall['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableStalls)): ?>
                    <label class="label"><span class="label-text-alt text-warning">No available stalls. All stalls are occupied or in maintenance.</span></label>
                <?php endif; ?>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Lease Contract (PDF)</span></label>
                <input type="file" name="contract" accept="application/pdf" class="file-input file-input-bordered w-full" />
                <div id="mm_current_contract" class="hidden mt-2">
                    <label class="label"><span class="label-text-alt">Current: <a id="mm_contract_link" href="#" target="_blank" class="link link-primary">View Document</a></span></label>
                    <label class="label cursor-pointer justify-start gap-2">
                        <input type="checkbox" name="remove_contract" value="1" class="checkbox checkbox-sm">
                        <span class="label-text">Remove current contract</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Start Date <span class="text-error">*</span></span></label>
                    <input type="date" name="start_date" id="mm_start_date" required value="<?= date('Y-m-d') ?>" class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">End Date <span class="text-error">*</span></span></label>
                    <input type="date" name="end_date" id="mm_end_date" required value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="input input-bordered" />
                </div>
            </div>

            <div class="form-control">
                <label class="label cursor-pointer justify-start gap-3">
                    <input id="mm_is_active" name="is_active" type="checkbox" checked class="checkbox checkbox-primary" />
                    <div>
                        <span class="label-text font-medium">Active Assignment</span>
                        <p class="text-xs opacity-50">Uncheck to deactivate (frees up the stall).</p>
                    </div>
                </label>
            </div>

            <div class="modal-action">
                <button type="button" onclick="closeMappingModal()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary" id="mappingSubmitBtn">Assign Stall</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script src="<?= \App\Config\App::basePath() ?>/public/assets/js/tom-select.complete.min.js"></script>
<script>
const basePath = '<?= \App\Config\App::basePath() ?>';
let tsUser = null;
let tsStall = null;

function initTomSelect() {
    // Destroy existing instances before reinitializing
    if (tsUser) { tsUser.destroy(); tsUser = null; }
    if (tsStall) { tsStall.destroy(); tsStall = null; }

    tsUser = new TomSelect('#mm_user_id', { placeholder: 'Search for a user...', allowEmptyOption: true, maxOptions: 200 });
    tsStall = new TomSelect('#mm_stall_id', { placeholder: 'Search for a stall...', allowEmptyOption: true, maxOptions: 200 });
}

function openMappingModal(mode, data = {}) {
    const modal = document.getElementById('mappingModal');
    const form = document.getElementById('mappingForm');
    const title = document.getElementById('mappingModalTitle');
    const submitBtn = document.getElementById('mappingSubmitBtn');
    const contractSection = document.getElementById('mm_current_contract');

    // Reset form
    form.reset();
    contractSection.classList.add('hidden');

    // Initialize Tom Select (needs to be on visible modal)
    modal.showModal();

    // Small delay to ensure modal is visible before TomSelect init
    setTimeout(() => {
        initTomSelect();

        if (mode === 'edit' && data.id) {
            title.textContent = 'Edit Stall Assignment';
            submitBtn.textContent = 'Update Assignment';
            form.action = basePath + '/admin/mappings/edit/' + data.id;

            tsUser.setValue(String(data.user_id));
            tsStall.setValue(String(data.stall_id));

            document.getElementById('mm_start_date').value = data.start_date || '';
            document.getElementById('mm_end_date').value = data.end_date || '';
            document.getElementById('mm_is_active').checked = !!data.is_active;

            if (data.contract_document) {
                contractSection.classList.remove('hidden');
                document.getElementById('mm_contract_link').href = basePath + '/files/contract/' + data.id;
            }
        } else {
            title.textContent = 'Assign Stall';
            submitBtn.textContent = 'Assign Stall';
            form.action = basePath + '/admin/mappings/create';
        }
    }, 100);
}

function closeMappingModal() {
    if (tsUser) { tsUser.destroy(); tsUser = null; }
    if (tsStall) { tsStall.destroy(); tsStall = null; }
    document.getElementById('mappingModal').close();
}

// Also clean up when modal is closed via backdrop or ESC
document.getElementById('mappingModal').addEventListener('close', () => {
    if (tsUser) { tsUser.destroy(); tsUser = null; }
    if (tsStall) { tsStall.destroy(); tsStall = null; }
});
</script>
