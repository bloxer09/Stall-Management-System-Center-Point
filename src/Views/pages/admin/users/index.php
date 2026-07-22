<?php use App\Services\RegistrationVerification; ?>
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <div class="flex gap-2">
            <a href="<?= \App\Config\App::basePath() ?>/admin/users/export?search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>"
                class="btn btn-outline btn-sm gap-2"><i data-lucide="download" class="h-4 w-4"></i> Export CSV</a>
            <button type="button" onclick="openUserModal('create')" class="btn btn-primary btn-sm gap-2"><i
                    data-lucide="user-plus" class="h-4 w-4"></i> New User</button>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4 flex flex-col sm:flex-row justify-between gap-4">
            <form action="<?= \App\Config\App::basePath() ?>/admin/users" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>"
                        placeholder="Search users..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/admin/users"
                        class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
            <form id="bulkActionForm" action="<?= \App\Config\App::basePath() ?>/admin/users/bulk" method="POST"
                class="join">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <input type="hidden" name="ids" id="bulkIds" value="">
                <select name="action" class="select select-bordered select-sm join-item">
                    <option value="">Bulk Actions</option>
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
                        <th><a href="?sort=name&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>"
                                class="inline-flex items-center gap-1">Name <i data-lucide="chevrons-up-down"
                                    class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=email&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>"
                                class="inline-flex items-center gap-1">Email <i data-lucide="chevrons-up-down"
                                    class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=phone&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>"
                                class="inline-flex items-center gap-1">Phone <i data-lucide="chevrons-up-down"
                                    class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=role&order=<?= $sortOrder === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>"
                                class="inline-flex items-center gap-1">Role <i data-lucide="chevrons-up-down"
                                    class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Registration Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 opacity-50">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover">
                                <th><?php if ($user['id'] != \App\Core\Auth::user()['id']): ?><input type="checkbox"
                                            value="<?= $user['id'] ?>" class="checkbox checkbox-sm item-checkbox" /><?php endif; ?>
                                </th>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-8"><span
                                                    class="text-xs"><?= strtoupper(substr($user['name'], 0, 1)) ?></span></div>
                                        </div>
                                        <span class="font-medium"><?= htmlspecialchars($user['name']) ?></span>
                                        <?php if ($user['id'] == \App\Core\Auth::user()['id']): ?>
                                            <div class="badge badge-ghost badge-sm">You</div><?php endif; ?>
                                    </div>
                                </td>
                                <td class="opacity-70"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="opacity-70">
                                    <?php if (!empty($user['phone'])): ?>
                                        <?= htmlspecialchars(RegistrationVerification::formatPhoneForDisplay((string) $user['phone'])) ?>
                                    <?php else: ?>
                                        <span class="opacity-50">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $roleBadge = 'badge-ghost';
                                    if ($user['role'] === 'admin')
                                        $roleBadge = 'badge-primary';
                                    elseif ($user['role'] === 'staff')
                                        $roleBadge = 'badge-secondary';
                                    else
                                        $roleBadge = 'badge-success';
                                    ?>
                                    <div class="badge <?= $roleBadge ?> badge-sm"><?= ucfirst($user['role']) ?></div>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-1 text-xs">
                                        <span
                                            class="badge badge-sm <?= !empty($user['email_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Email
                                            <?= !empty($user['email_verified_at']) ? 'Verified' : 'Not Verified' ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-1 text-xs">
                                        <span
                                            class="badge badge-sm <?= !empty($user['phone_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Phone
                                            <?= !empty($user['phone_verified_at']) ? 'Verified' : 'Not Verified' ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-sm opacity-60"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-ghost btn-xs" onclick="openUserModal('edit', <?= htmlspecialchars(json_encode([
                                        'id' => $user['id'],
                                        'name' => $user['name'],
                                        'email' => $user['email'],
                                        'phone' => $user['phone'] ?? '',
                                        'role' => $user['role'],
                                    ])) ?>)">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <?php if ($user['id'] != \App\Core\Auth::user()['id']): ?>
                                        <form action="<?= \App\Config\App::basePath() ?>/admin/users/delete/<?= $user['id'] ?>"
                                            method="POST" class="inline" data-confirm-message="Delete this user?"
                                            data-confirm-title="Delete User" data-confirm-confirm-label="Delete"
                                            data-confirm-variant="error">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= \App\Core\Session::csrfToken() ?>"><button type="submit"
                                                class="btn btn-ghost btn-xs text-error"><i data-lucide="trash-2"
                                                    class="w-4 h-4"></i></button>
                                        </form>
                                    <?php endif; ?>
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

<!-- Create / Edit User Modal -->
<dialog id="userModal" class="modal">
    <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4" id="userModalTitle">New User</h3>
        <form id="userForm" action="" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Full Name <span
                            class="text-error">*</span></span></label>
                <input type="text" name="name" id="mu_name" required class="input input-bordered" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Email Address <span
                            class="text-error">*</span></span></label>
                <input type="email" name="email" id="mu_email" required class="input input-bordered" />
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Phone Number <span
                            class="text-error">*</span></span></label>
                <input type="tel" name="phone" id="mu_phone" required placeholder="+63 917 123 4567"
                    class="input input-bordered" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium" id="mu_password_label">Password <span
                                class="text-error">*</span></span></label>
                    <input type="password" name="password" id="mu_password" class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Role</span></label>
                    <select id="mu_role" name="role" class="select select-bordered">
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="modal-action">
                <button type="button" onclick="document.getElementById('userModal').close()"
                    class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary" id="userSubmitBtn">Create User</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const basePath = '<?= \App\Config\App::basePath() ?>';

    function openUserModal(mode, data = {}) {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const title = document.getElementById('userModalTitle');
        const submitBtn = document.getElementById('userSubmitBtn');
        const passwordField = document.getElementById('mu_password');
        const passwordLabel = document.getElementById('mu_password_label');

        form.reset();

        if (mode === 'edit' && data.id) {
            title.textContent = 'Edit User';
            submitBtn.textContent = 'Update User';
            form.action = basePath + '/admin/users/edit/' + data.id;
            document.getElementById('mu_name').value = data.name || '';
            document.getElementById('mu_email').value = data.email || '';
            document.getElementById('mu_phone').value = data.phone || '';
            document.getElementById('mu_role').value = data.role || 'user';
            passwordField.removeAttribute('required');
            passwordLabel.innerHTML = 'Password <span class="opacity-50 font-normal">(blank = keep)</span>';
        } else {
            title.textContent = 'New User';
            submitBtn.textContent = 'Create User';
            form.action = basePath + '/admin/users/create';
            passwordField.setAttribute('required', 'required');
            passwordLabel.innerHTML = 'Password <span class="text-error">*</span>';
        }

        modal.showModal();
    }

    // Select all & bulk
    document.getElementById('selectAll').addEventListener('change', function () { document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked); });
    async function submitBulkAction() {
        const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
        if (!selected.length) {
            await window.appDialog.alert('Select at least one user to continue.', { title: 'Nothing selected', variant: 'warning' });
            return;
        }
        const actionSelect = document.querySelector('select[name="action"]');
        const action = actionSelect.value;
        if (!action) {
            await window.appDialog.alert('Choose a bulk action first.', { title: 'Action required', variant: 'warning' });
            return;
        }
        const confirmed = await window.appDialog.confirm(
            `Perform "${actionSelect.options[actionSelect.selectedIndex].text}" on ${selected.length} user(s)?`,
            { title: 'Confirm bulk action', confirmLabel: 'Apply', variant: action === 'delete' ? 'error' : 'primary' }
        );
        if (!confirmed) return;
        document.getElementById('bulkIds').value = selected.join(',');
        document.getElementById('bulkActionForm').submit();
    }
</script>