<aside class="menu bg-base-300 min-h-full w-72 p-4 flex flex-col">
    <!-- Brand -->
    <a href="<?= \App\Config\App::basePath() ?>/user/dashboard" class="flex items-center gap-2 px-4 py-3 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2 2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>
        <span class="text-xl font-bold">Stall<span class="text-primary">Manage</span></span>
    </a>

    <ul class="menu menu-md rounded-box gap-1 flex-1">
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/user/dashboard" class="<?= strpos($_SERVER['REQUEST_URI'], '/user/dashboard') !== false ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                My Stalls
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/user/stalls/map" class="<?= strpos($_SERVER['REQUEST_URI'], '/user/stalls/map') !== false ? 'active' : '' ?>">
                <i data-lucide="map" class="w-5 h-5"></i>
                Stall Directory Map
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/user/payments" class="<?= strpos($_SERVER['REQUEST_URI'], '/user/payments') !== false ? 'active' : '' ?>">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                Payment Records
            </a>
        </li>
        <li>
            <a href="<?= \App\Config\App::basePath() ?>/user/tickets" class="<?= strpos($_SERVER['REQUEST_URI'], '/user/tickets') !== false ? 'active' : '' ?>">
                <i data-lucide="ticket" class="w-5 h-5"></i>
                Tickets
            </a>
        </li>
        <li>
            <a href="javascript:void(0)" onclick="document.getElementById('userProfileModal').showModal()">
                <i data-lucide="user-circle" class="w-5 h-5"></i>
                My Profile
            </a>
        </li>
    </ul>

    <!-- Footer -->
    <div class="border-t border-base-content/10 pt-4 mt-4">
        <ul class="menu menu-md rounded-box gap-1">
            <li>
                <a href="javascript:void(0)" onclick="document.getElementById('userChangePasswordModal').showModal()">
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
    $__uProfileUser = \App\Core\Auth::user();
    $__uProfilePicUrl = !empty($__uProfileUser['profile_picture']) ? \App\Config\App::path('/' . ltrim($__uProfileUser['profile_picture'], '/')) : null;
?>

<!-- Profile Modal -->
<dialog id="userProfileModal" class="modal">
    <div class="modal-box max-w-lg max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4">My Profile</h3>
        <form action="<?= \App\Config\App::basePath() ?>/user/profile" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

            <div class="flex items-center gap-4">
                <div class="avatar">
                    <div class="w-20 rounded-full border border-base-200 bg-base-200 overflow-hidden">
                        <?php if ($__uProfilePicUrl): ?>
                            <img src="<?= htmlspecialchars($__uProfilePicUrl) ?>" alt="Profile">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold opacity-60">
                                <?= strtoupper(substr($__uProfileUser['name'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-1 space-y-2">
                    <input type="file" name="profile_picture" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input file-input-bordered file-input-sm w-full" />
                    <p class="text-xs opacity-50">JPG, PNG, WEBP, GIF (max 2MB)</p>
                    <?php if (!empty($__uProfileUser['profile_picture'])): ?>
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
                    <input type="text" value="<?= htmlspecialchars($__uProfileUser['name'] ?? '') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Email</span></label>
                    <input type="text" value="<?= htmlspecialchars($__uProfileUser['email'] ?? '') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Phone</span></label>
                    <input type="text" value="<?= htmlspecialchars(!empty($__uProfileUser['phone']) ? \App\Services\RegistrationVerification::formatPhoneForDisplay((string) $__uProfileUser['phone']) : 'Not set') ?>" class="input input-bordered input-sm bg-base-200/70 cursor-not-allowed" readonly disabled />
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Verification</span></label>
                    <div class="flex flex-wrap gap-1 pt-1">
                        <span class="badge badge-sm <?= !empty($__uProfileUser['email_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Email <?= !empty($__uProfileUser['email_verified_at']) ? '✓' : '…' ?></span>
                        <span class="badge badge-sm <?= !empty($__uProfileUser['phone_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Phone <?= !empty($__uProfileUser['phone_verified_at']) ? '✓' : '…' ?></span>
                    </div>
                </div>
            </div>
            <p class="text-xs opacity-50">Name, email, and phone cannot be changed from this page.</p>

            <div class="modal-action">
                <button type="button" onclick="document.getElementById('userProfileModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- Change Password Modal -->
<dialog id="userChangePasswordModal" class="modal">
    <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4">Change Password</h3>
        <form action="<?= \App\Config\App::basePath() ?>/user/change-password" method="POST" class="space-y-4">
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
                    <input id="user_new_pw" name="password" type="password" required minlength="8" placeholder="Minimum 8 characters" class="grow" />
                </label>
                <div id="userStrengthBar" class="hidden mt-2">
                    <div class="flex gap-1 mb-1">
                        <div id="us1" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="us2" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="us3" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        <div id="us4" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                    </div>
                    <p id="userStrengthLabel" class="text-xs opacity-50"></p>
                </div>
            </div>
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Confirm New Password</span></label>
                <label class="input input-bordered flex items-center gap-2">
                    <i data-lucide="lock-keyhole" class="w-4 h-4 opacity-50"></i>
                    <input id="user_confirm_pw" name="password_confirmation" type="password" required minlength="8" placeholder="Repeat your new password" class="grow" />
                </label>
                <p id="userMatchMsg" class="hidden text-xs mt-1 text-error">Passwords do not match</p>
            </div>
            <div class="modal-action mt-2">
                <button type="button" onclick="document.getElementById('userChangePasswordModal').close()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
(function(){
    const pw = document.getElementById('user_new_pw');
    const confirm = document.getElementById('user_confirm_pw');
    const bar = document.getElementById('userStrengthBar');
    const label = document.getElementById('userStrengthLabel');
    const segs = ['us1','us2','us3','us4'].map(id => document.getElementById(id));
    const colors = ['bg-red-500','bg-orange-400','bg-yellow-400','bg-green-500'];
    const lbls = ['Weak','Fair','Good','Strong'];
    const matchMsg = document.getElementById('userMatchMsg');
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
        checkUserMatch();
    });
    function checkUserMatch() {
        if (!confirm.value) { matchMsg.classList.add('hidden'); return; }
        matchMsg.classList.toggle('hidden', pw.value === confirm.value);
    }
    confirm.addEventListener('input', checkUserMatch);
})();
</script>

