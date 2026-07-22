<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/admin/users" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label" for="name"><span class="label-text font-medium">Full Name <span class="text-error">*</span></span></label>
                    <input type="text" name="name" id="name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label" for="email"><span class="label-text font-medium">Email Address <span class="text-error">*</span></span></label>
                    <input type="email" name="email" id="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="input input-bordered" />
                </div>
                <div class="form-control">
                    <label class="label" for="phone"><span class="label-text font-medium">Phone Number <span class="text-error">*</span></span></label>
                    <input type="tel" name="phone" id="phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+63 917 123 4567" class="input input-bordered" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="password"><span class="label-text font-medium">Password <?= $user ? '<span class="opacity-50 font-normal">(blank = keep)</span>' : '<span class="text-error">*</span>' ?></span></label>
                        <input type="password" name="password" id="password" <?= $user ? '' : 'required' ?> class="input input-bordered" />
                    </div>
                    <div class="form-control">
                        <label class="label" for="role"><span class="label-text font-medium">Role</span></label>
                        <select id="role" name="role" class="select select-bordered">
                            <option value="user" <?= isset($user['role']) && $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                            <option value="staff" <?= isset($user['role']) && $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                            <option value="admin" <?= isset($user['role']) && $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="divider"></div>
                <div class="flex justify-end gap-2">
                    <a href="<?= \App\Config\App::basePath() ?>/admin/users" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= $user ? 'Update User' : 'Create User' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

