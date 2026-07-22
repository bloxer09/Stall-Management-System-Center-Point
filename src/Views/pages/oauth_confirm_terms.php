<?php
$appName = \App\Config\App::name();
$oauthPending = $oauthPending ?? [];
$email = $oauthPending['email'] ?? '';
$name = $oauthPending['name'] ?? '';
$provider = ucfirst($oauthPending['provider'] ?? 'social');
?>
<div class="min-h-[85vh] flex flex-col justify-center py-12 px-4 bg-base-200">
    <div class="mx-auto w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <i data-lucide="store" class="w-8 h-8 text-primary"></i>
                <span class="text-2xl font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h2 class="text-3xl font-extrabold">Complete Registration</h2>
            <p class="mt-2 text-sm opacity-60">
                You're almost there! Agree to the policies below to create your account.
            </p>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body space-y-4">
                <div class="flex items-center gap-3 bg-base-200/50 p-4 rounded-xl text-sm">
                    <div class="avatar placeholder">
                        <div class="bg-neutral text-neutral-content rounded-full w-10">
                            <span><?= strtoupper(substr($name, 0, 1)) ?></span>
                        </div>
                    </div>
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs opacity-60"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?> (via <?= $provider ?>)</p>
                    </div>
                </div>

                <?php if (\App\Core\Session::hasFlash('error')): ?>
                    <div class="alert alert-error text-sm py-2">
                        <span><?= htmlspecialchars(\App\Core\Session::flash('error'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= \App\Config\App::basePath() ?>/auth/confirm-terms" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    
                    <div class="form-control">
                        <label class="label" for="password"><span class="label-text font-medium">Create Password</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                            <input id="oauth_password" name="password" type="password" required
                                placeholder="At least 8 characters" class="grow" />
                            <button type="button" class="btn btn-ghost btn-xs btn-circle"
                                id="toggle_oauth_password" aria-label="Show password">
                                <svg id="eye_open_1" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                </svg>
                                <svg id="eye_closed_1" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4"
                                    fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m3 3 18 18M10.584 10.587A2 2 0 0 0 13.413 13.4M9.88 5.09A10.94 10.94 0 0 1 12 5c4.477 0 8.268 2.943 9.542 7a11.02 11.02 0 0 1-4.132 5.411M6.228 6.228A10.97 10.97 0 0 0 2.458 12C3.732 16.057 7.523 19 12 19a10.96 10.96 0 0 0 5.772-1.628" />
                                </svg>
                            </button>
                        </label>
                        <div class="mt-2 space-y-1">
                            <progress id="oauth_strength_meter" class="progress progress-error w-full" value="0" max="100"></progress>
                            <div class="flex items-center justify-between text-xs">
                                <span id="oauth_strength_label" class="font-medium opacity-70">Password strength: Too weak</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label" for="password_confirmation"><span class="label-text font-medium">Confirm Password</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                            <input id="oauth_password_confirmation" name="password_confirmation" type="password" required
                                placeholder="Confirm your password" class="grow" />
                            <button type="button" class="btn btn-ghost btn-xs btn-circle"
                                id="toggle_oauth_password_confirm" aria-label="Show confirm password">
                                <svg id="eye_open_2" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                    viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                </svg>
                                <svg id="eye_closed_2" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4"
                                    fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m3 3 18 18M10.584 10.587A2 2 0 0 0 13.413 13.4M9.88 5.09A10.94 10.94 0 0 1 12 5c4.477 0 8.268 2.943 9.542 7a11.02 11.02 0 0 1-4.132 5.411M6.228 6.228A10.97 10.97 0 0 0 2.458 12C3.732 16.057 7.523 19 12 19a10.96 10.96 0 0 0 5.772-1.628" />
                                </svg>
                            </button>
                        </label>
                        <p id="oauth_match_hint" class="mt-1 text-xs opacity-60">Re-enter the same password to confirm it.</p>
                    </div>

                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3 items-start">
                            <input name="terms" type="checkbox" required class="checkbox checkbox-primary checkbox-sm mt-0.5" />
                            <span class="label-text leading-snug">
                                I agree to the 
                                <a href="<?= \App\Config\App::basePath() ?>/terms" target="_blank" class="link link-primary font-medium">Terms & Conditions</a> 
                                and 
                                <a href="<?= \App\Config\App::basePath() ?>/privacy" target="_blank" class="link link-primary font-medium">Privacy Policy</a>.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">Complete Registration</button>
                </form>

                <div class="text-center text-xs opacity-50">
                    <a href="<?= \App\Config\App::basePath() ?>/?login=1" class="link link-hover">Cancel and Return to Sign-in</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function initOauthPasswordValidation() {
        const pw = document.getElementById('oauth_password');
        const confirm = document.getElementById('oauth_password_confirmation');
        const meter = document.getElementById('oauth_strength_meter');
        const label = document.getElementById('oauth_strength_label');
        const hint = document.getElementById('oauth_match_hint');
        const toggleBtn1 = document.getElementById('toggle_oauth_password');
        const toggleBtn2 = document.getElementById('toggle_oauth_password_confirm');

        if (!pw || !confirm) return;

        function setupToggle(btn, input, eyeOpenId, eyeClosedId) {
            if (!btn || !input) return;
            btn.addEventListener('click', () => {
                const isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                document.getElementById(eyeOpenId).classList.toggle('hidden', isPw);
                document.getElementById(eyeClosedId).classList.toggle('hidden', !isPw);
            });
        }
        setupToggle(toggleBtn1, pw, 'eye_open_1', 'eye_closed_1');
        setupToggle(toggleBtn2, confirm, 'eye_open_2', 'eye_closed_2');

        function evaluateStrength(val) {
            let score = 0;
            if (!val) return { score: 0, text: 'Too weak', color: 'progress-error' };
            if (val.length >= 8) score += 25;
            if (/[A-Z]/.test(val)) score += 25;
            if (/[0-9]/.test(val)) score += 25;
            if (/[^A-Za-z0-9]/.test(val)) score += 25;

            if (score <= 25) return { score: 25, text: 'Weak', color: 'progress-error' };
            if (score <= 50) return { score: 50, text: 'Fair', color: 'progress-warning' };
            if (score <= 75) return { score: 75, text: 'Good', color: 'progress-info' };
            return { score: 100, text: 'Strong', color: 'progress-success' };
        }

        function updateStrength() {
            const res = evaluateStrength(pw.value);
            meter.value = res.score;
            meter.className = `progress ${res.color} w-full`;
            label.textContent = `Password strength: ${res.text}`;
        }

        function checkMatch() {
            if (!confirm.value) { hint.textContent = 'Re-enter the same password.'; hint.className = 'text-xs mt-1 opacity-60'; return; }
            if (confirm.value === pw.value) { hint.textContent = 'Passwords match.'; hint.className = 'text-xs mt-1 text-success'; }
            else { hint.textContent = 'Passwords do not match.'; hint.className = 'text-xs mt-1 text-error'; }
        }

        pw.addEventListener('input', () => { updateStrength(); checkMatch(); });
        confirm.addEventListener('input', checkMatch);
    })();
</script>
