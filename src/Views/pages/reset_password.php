<?php $appName = \App\Config\App::name(); ?>
<div class="min-h-[85vh] flex flex-col justify-center py-12 px-4 bg-base-200">
    <div class="mx-auto w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <i data-lucide="store" class="w-8 h-8 text-primary"></i>
                <span class="text-2xl font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h2 class="text-3xl font-extrabold">Reset Password</h2>
            <p class="mt-2 text-sm opacity-60">Enter your new password below.</p>
        </div>
        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form action="<?= \App\Config\App::basePath() ?>/reset-password" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                    <div class="form-control">
                        <label class="label" for="password"><span class="label-text font-medium">New Password</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                            <input id="password" name="password" type="password" required placeholder="Min 8 characters" class="grow" />
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label" for="password_confirmation"><span class="label-text font-medium">Confirm Password</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                            <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="Confirm your new password" class="grow" />
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

