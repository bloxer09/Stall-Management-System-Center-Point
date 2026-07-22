<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold">Verify Password Change</h1>
        <p class="text-sm opacity-60 mt-1">Confirm your request with the verification code</p>
    </div>
    
    <div class="card bg-base-100 shadow-sm max-w-md">
        <div class="card-body">
            <div class="alert bg-base-200 text-xs py-3 mb-4 rounded-xl">
                <div>
                    <p>We've sent a 6-digit verification code to your email. The code is valid for 10 minutes.</p>
                </div>
            </div>

            <?php if (\App\Core\Session::hasFlash('error')): ?>
                <div class="alert alert-error text-sm py-2 mb-4 rounded-xl">
                    <span><?= htmlspecialchars(\App\Core\Session::flash('error'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <?php if (\App\Core\Session::hasFlash('success')): ?>
                <div class="alert alert-success text-sm py-2 mb-4 rounded-xl">
                    <span><?= htmlspecialchars(\App\Core\Session::flash('success'), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="form-control">
                    <label class="label" for="code">
                        <span class="label-text font-medium">6-Digit Verification Code</span>
                    </label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="key-round" class="w-4 h-4 opacity-50"></i>
                        <input id="code" name="code" type="text" maxlength="6" pattern="\d{6}" required placeholder="123456" class="grow tracking-[0.5em] font-bold text-center text-lg" autofocus autocomplete="off" />
                    </label>
                </div>

                <div class="flex gap-3">
                    <a href="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost flex-1">Cancel</a>
                    <button type="submit" class="btn btn-primary flex-1">Verify & Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
