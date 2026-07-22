<?php
$appName = \App\Config\App::name();
$loginVerifyFlow = $loginVerifyFlow ?? [];
$resendInSeconds = (int) ($loginVerifyFlow['resend_in_seconds'] ?? 0);
$maskedEmail = $loginVerifyFlow['masked_email'] ?? 'your email';
?>
<div class="min-h-[85vh] flex flex-col justify-center py-12 px-4 bg-base-200">
    <div class="mx-auto w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <i data-lucide="store" class="w-8 h-8 text-primary"></i>
                <span class="text-2xl font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h2 class="text-3xl font-extrabold">Two-Step Verification</h2>
            <p class="mt-2 text-sm opacity-60">
                To keep your account secure, we sent a verification code.
            </p>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body space-y-4">
                <div class="alert bg-base-200 text-xs">
                    <div>
                        <p>We've sent a 6-digit verification code to <strong><?= htmlspecialchars($maskedEmail, ENT_QUOTES, 'UTF-8') ?></strong>. The code will expire in 10 minutes.</p>
                    </div>
                </div>

                <?php if (\App\Core\Session::hasFlash('error')): ?>
                    <div class="alert alert-error text-sm py-2">
                        <span><?= htmlspecialchars(\App\Core\Session::flash('error'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <?php if (\App\Core\Session::hasFlash('success')): ?>
                    <div class="alert alert-success text-sm py-2">
                        <span><?= htmlspecialchars(\App\Core\Session::flash('success'), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= \App\Config\App::basePath() ?>/login/verify" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    
                    <div class="form-control">
                        <label class="label" for="code">
                            <span class="label-text font-medium">6-Digit Verification Code</span>
                        </label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="key-round" class="w-4 h-4 opacity-50"></i>
                            <input id="code" name="code" type="text" maxlength="6" pattern="\d{6}" required placeholder="123456" class="grow tracking-[0.5em] font-bold text-center text-lg" autofocus autocomplete="one-time-code" />
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">Confirm Code</button>
                </form>

                <div class="divider"></div>

                <div class="flex justify-between items-center text-sm">
                    <form action="<?= \App\Config\App::basePath() ?>/login/verify/resend" method="POST" id="resend-form">
                        <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                        <button type="submit" id="resend-btn" class="btn btn-ghost btn-sm" <?= $resendInSeconds > 0 ? 'disabled' : '' ?>>
                            Resend Code
                        </button>
                    </form>
                    <span id="cooldown-timer" class="text-xs opacity-60 font-medium <?= $resendInSeconds <= 0 ? 'hidden' : '' ?>">
                        Resend in <span id="seconds-left"><?= $resendInSeconds ?></span>s
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    var resendInSeconds = <?= $resendInSeconds ?>;
    var resendBtn = document.getElementById("resend-btn");
    var cooldownTimer = document.getElementById("cooldown-timer");
    var secondsLeftSpan = document.getElementById("seconds-left");

    if (resendInSeconds > 0) {
        var interval = setInterval(function () {
            resendInSeconds--;
            secondsLeftSpan.textContent = resendInSeconds;
            if (resendInSeconds <= 0) {
                clearInterval(interval);
                resendBtn.removeAttribute("disabled");
                cooldownTimer.classList.add("hidden");
            }
        }, 1000);
    }
});
</script>
