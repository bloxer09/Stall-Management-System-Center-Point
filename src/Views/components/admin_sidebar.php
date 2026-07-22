<aside class="menu bg-base-300 min-h-full w-72 p-4 flex flex-col">
    <!-- Brand -->
    <a href="<?= \App\Config\App::basePath() ?>/admin/dashboard" class="flex items-center gap-2 px-4 py-3 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2 2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>
        <span class="text-xl font-bold">Stall<span class="text-primary">Manage</span></span>
    </a>

    <!-- Navigation -->
    <ul class="menu menu-md rounded-box gap-1 flex-1">
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/dashboard" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/stalls" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/stalls') !== false && strpos($_SERVER['REQUEST_URI'], '/admin/stalls/map') === false ? 'active' : '' ?>">
                <i data-lucide="store" class="w-5 h-5"></i>
                Manage Stalls
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/stalls/map" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/stalls/map') !== false ? 'active' : '' ?>">
                <i data-lucide="map" class="w-5 h-5"></i>
                Visual Stall Map
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/stall-locations" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/stall-locations') !== false ? 'active' : '' ?>">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
                Stall Locations
            </a>
        </li>
        <?php if (\App\Core\Auth::isAdmin()): ?>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/users" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/users') !== false ? 'active' : '' ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                Manage Users
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/mappings" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/mappings') !== false ? 'active' : '' ?>">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
                Stall Allocations
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/payments" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/payments') !== false ? 'active' : '' ?>">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                Payments
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/analytics" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/analytics') !== false ? 'active' : '' ?>">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                Financial Analytics
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/utilities" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/utilities') !== false ? 'active' : '' ?>">
                <i data-lucide="zap" class="w-5 h-5"></i>
                Metered Utilities
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/tickets" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/tickets') !== false ? 'active' : '' ?>">
                <i data-lucide="ticket" class="w-5 h-5"></i>
                Support Tickets
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/audit-logs" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/audit-logs') !== false ? 'active' : '' ?>">
                <i data-lucide="history" class="w-5 h-5"></i>
                Audit Logs
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/error-logs" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/error-logs') !== false ? 'active' : '' ?>">
                <i data-lucide="triangle-alert" class="w-5 h-5"></i>
                Error Logs
            </a>
        </li>
        <?php if (\App\Core\Auth::isAdmin()): ?>
        <li class="menu-title mt-4"><span>Settings</span></li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/settings/privacy" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/privacy') !== false ? 'active' : '' ?>">
                <i data-lucide="shield" class="w-5 h-5"></i>
                Privacy Policy
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/settings/terms" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/terms') !== false ? 'active' : '' ?>">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                Terms & Conditions
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/admin/settings/backups" class="<?= strpos($_SERVER['REQUEST_URI'], '/admin/settings/backups') !== false ? 'active' : '' ?>">
                <i data-lucide="database" class="w-5 h-5"></i>
                Database Backups
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Footer actions -->
    <div class="border-t border-base-content/10 pt-4 mt-4">
        <ul class="menu menu-md rounded-box gap-1">
            <li>
                <a href="javascript:void(0)" onclick="document.getElementById('profileModal').showModal()">
                    <i data-lucide="user-circle" class="w-5 h-5"></i>
                    Profile
                </a>
            </li>
            <li>
                <a href="javascript:void(0)" onclick="document.getElementById('changePasswordModal').showModal()">
                    <i data-lucide="key-round" class="w-5 h-5"></i>
                    Change Password
                </a>
            </li>
            <li>
                <a href="<?= \App\Config\App::basePath() ?>/logout" class="text-error hover:bg-error/10">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    Sign out
                </a>
            </li>
        </ul>
        <p class="text-xs opacity-50 text-center mt-3">&copy; <?= date('Y') ?> <?= htmlspecialchars(\App\Config\App::name(), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
</aside>

<?php
    use App\Services\RegistrationVerification;
    $__profileUser = \App\Core\Auth::user();
    $__profilePicUrl = !empty($__profileUser['profile_picture']) ? \App\Config\App::path('/' . ltrim($__profileUser['profile_picture'], '/')) : null;
?>

<!-- Profile Modal -->
<dialog id="profileModal" class="modal">
    <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4">My Profile</h3>
        <form action="<?= \App\Config\App::basePath() ?>/admin/profile" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="flex items-center gap-4">
                <div class="avatar">
                    <div class="w-20 rounded-full border border-base-200 bg-base-200 overflow-hidden">
                        <?php if ($__profilePicUrl): ?>
                            <img src="<?= htmlspecialchars($__profilePicUrl) ?>" alt="Profile">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold opacity-60">
                                <?= strtoupper(substr($__profileUser['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-1 space-y-2">
                    <input type="file" name="profile_picture" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input file-input-bordered file-input-sm w-full" />
                    <p class="text-xs opacity-50">JPG, PNG, WEBP, GIF (max 2MB)</p>
                    <?php if (!empty($__profileUser['profile_picture'])): ?>
                        <label class="label cursor-pointer justify-start gap-2 p-0">
                            <input type="checkbox" name="remove_profile_picture" value="1" class="checkbox checkbox-sm">
                            <span class="label-text text-sm">Remove picture</span>
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Full Name</span></label>
                    <input type="text" value="<?= htmlspecialchars($__profileUser['name'] ?? '') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Email</span></label>
                    <input type="text" value="<?= htmlspecialchars($__profileUser['email'] ?? '') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Phone</span></label>
                    <input type="text" value="<?= htmlspecialchars(!empty($__profileUser['phone']) ? RegistrationVerification::formatPhoneForDisplay((string) $__profileUser['phone']) : 'Not set') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Verification</span></label>
                    <div class="flex flex-wrap gap-1 pt-1">
                        <span class="badge badge-sm <?= !empty($__profileUser['email_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Email <?= !empty($__profileUser['email_verified_at']) ? '✓' : '…' ?></span>
                        <span class="badge badge-sm <?= !empty($__profileUser['phone_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Phone <?= !empty($__profileUser['phone_verified_at']) ? '✓' : '…' ?></span>
                    </div>
                </div>
            </div>
            <p class="text-xs opacity-50">Name, email, and phone cannot be changed from this page.</p>

            <div class="modal-action">
                <button type="button" onclick="document.getElementById('profileModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Change Password Modal -->
<dialog id="changePasswordModal" class="modal">
    <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4">Change Password</h3>
        <form action="<?= \App\Config\App::basePath() ?>/admin/change-password" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Current Password</span></label>
                <label class="input input-bordered flex items-center gap-2">
                    <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                    <input name="current_password" type="password" required placeholder="Enter your current password" class="grow" />
                </label>
            </div>
            <div class="divider"></div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">New Password</span></label>
                <label class="input input-bordered flex items-center gap-2">
                    <i data-lucide="lock-keyhole" class="w-4 h-4 opacity-50"></i>
                    <input id="admin_new_pw" name="password" type="password" required minlength="8" placeholder="Minimum 8 characters" class="grow" />
                </label>
                <div id="adminStrengthBar" class="hidden mt-2">
                    <div class="flex gap-1 mb-1">
                        <div id="as1" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="as2" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="as3" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="as4" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                    </div>
                    <p id="adminStrengthLabel" class="text-xs opacity-50"></p>
                </div>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Confirm New Password</span></label>
                <label class="input input-bordered flex items-center gap-2">
                    <i data-lucide="lock-keyhole" class="w-4 h-4 opacity-50"></i>
                    <input id="admin_confirm_pw" name="password_confirmation" type="password" required minlength="8" placeholder="Repeat your new password" class="grow" />
                </label>
                <p id="adminMatchMsg" class="hidden text-xs mt-1 text-error">Passwords do not match</p>
            </div>
            <div class="modal-action mt-2">
                <button type="button" onclick="document.getElementById('changePasswordModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
(function(){
    const pw = document.getElementById('admin_new_pw');
    const confirm = document.getElementById('admin_confirm_pw');
    const bar = document.getElementById('adminStrengthBar');
    const label = document.getElementById('adminStrengthLabel');
    const segs = ['as1','as2','as3','as4'].map(id => document.getElementById(id));
    const colors = ['bg-red-500','bg-orange-400','bg-yellow-400','bg-green-500'];
    const lbls = ['Weak','Fair','Good','Strong'];
    const matchMsg = document.getElementById('adminMatchMsg');
    if (!pw) return;
    pw.addEventListener('input', () => {
        const val = pw.value;
        if (!val) { bar.classList.add('hidden'); return; }
        bar.classList.remove('hidden');
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        score = Math.max(1, score);
        segs.forEach((s, i) => { s.className = 'h-1.5 flex-1 rounded transition-colors ' + (i < score ? colors[score-1] : 'bg-base-300'); });
        label.textContent = lbls[score-1];
        checkAdminMatch();
    });
    function checkAdminMatch() {
        if (!confirm.value) { matchMsg.classList.add('hidden'); return; }
        matchMsg.classList.toggle('hidden', pw.value === confirm.value);
    }
    confirm.addEventListener('input', checkAdminMatch);
})();
</script>

