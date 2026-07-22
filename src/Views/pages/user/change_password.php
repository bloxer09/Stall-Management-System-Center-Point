<div class="space-y-4">
    <?php $changePasswordFormAction = $changePasswordFormAction ?? (\App\Config\App::basePath() . '/user/change-password'); ?>
    <div>
        <h1 class="text-2xl font-bold">Change Password</h1>
        <p class="text-sm opacity-60 mt-1">Update your account password</p>
    </div>
    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form action="<?= htmlspecialchars($changePasswordFormAction, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="form-control">
                    <label class="label" for="current_password"><span class="label-text font-medium">Current Password</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                        <input id="current_password" name="current_password" type="password" required placeholder="Enter your current password" class="grow" />
                    </label>
                </div>

                <div class="divider"></div>

                <div class="form-control">
                    <label class="label" for="password"><span class="label-text font-medium">New Password</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="lock-keyhole" class="w-4 h-4 opacity-50"></i>
                        <input id="password" name="password" type="password" required minlength="8" placeholder="Minimum 8 characters" class="grow" />
                    </label>
                    <!-- Strength meter -->
                    <div id="strength-bar" class="hidden mt-2">
                        <p class="text-xs opacity-50 mb-1">Password strength</p>
                        <div class="flex gap-1 mb-1">
                            <div id="s1" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                            <div id="s2" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                            <div id="s3" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                            <div id="s4" class="h-1.5 flex-1 rounded bg-base-300 transition-colors"></div>
                        </div>
                        <p id="strength-label" class="text-xs opacity-50"></p>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="password_confirmation"><span class="label-text font-medium">Confirm New Password</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="lock-keyhole" class="w-4 h-4 opacity-50"></i>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" placeholder="Repeat your new password" class="grow" />
                    </label>
                    <p id="match-msg" class="hidden text-xs mt-1 text-error">Passwords do not match</p>
                </div>

                <button type="submit" class="btn btn-primary w-full">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
// Strength meter
const pwInput = document.getElementById('password');
const bar = document.getElementById('strength-bar');
const label = document.getElementById('strength-label');
const segs = ['s1','s2','s3','s4'].map(id => document.getElementById(id));
const colors = ['bg-red-500','bg-orange-400','bg-yellow-400','bg-green-500'];
const lbls = ['Weak','Fair','Good','Strong'];

pwInput.addEventListener('input', () => {
    const val = pwInput.value;
    if (!val) { bar.classList.add('hidden'); return; }
    bar.classList.remove('hidden');
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    score = Math.max(1, score);
    segs.forEach((s, i) => {
        s.className = 'h-1.5 flex-1 rounded transition-colors ' + (i < score ? colors[score-1] : 'bg-base-300');
    });
    label.textContent = lbls[score-1];
    checkMatch();
});

const confirmInput = document.getElementById('password_confirmation');
const matchMsg = document.getElementById('match-msg');
function checkMatch() {
    if (!confirmInput.value) { matchMsg.classList.add('hidden'); return; }
    matchMsg.classList.toggle('hidden', pwInput.value === confirmInput.value);
}
confirmInput.addEventListener('input', checkMatch);
</script>

