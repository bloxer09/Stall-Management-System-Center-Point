<?php $appName = \App\Config\App::name(); ?>
<div class="min-h-[85vh] flex flex-col justify-center py-12 px-4 bg-base-200">
    <div class="mx-auto w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-4">
                <i data-lucide="store" class="w-8 h-8 text-primary"></i>
                <span class="text-2xl font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h2 class="text-3xl font-extrabold">Forgot your password?</h2>
            <p class="mt-2 text-sm opacity-60">Enter your email and we'll send you a reset link.</p>
        </div>

        <div class="card bg-base-100 shadow-xl">
            <div class="card-body">
                <form action="<?= \App\Config\App::basePath() ?>/forgot-password" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                    <div class="form-control">
                        <label class="label" for="email"><span class="label-text font-medium">Email address</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4 opacity-50"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" class="grow" />
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
                </form>
                <div class="text-center mt-4">
                    <a href="<?= \App\Config\App::basePath() ?>/?login=1" class="link link-primary text-sm inline-flex items-center gap-1">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> Back to login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

