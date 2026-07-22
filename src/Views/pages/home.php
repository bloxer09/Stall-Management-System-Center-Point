<?php $appName = \App\Config\App::name(); ?>
<!-- Hero Section -->
<div class="hero min-h-[500px] bg-gradient-to-br from-primary to-primary-focus text-primary-content">
    <div class="hero-content text-center">
        <div class="max-w-2xl">
            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight mb-6 drop-shadow-md">Streamline Your Stall
                Management</h1>
            <p class="text-lg md:text-xl mb-8 opacity-90">Manage allocations, track payments, and maintain your stalls
                effortlessly with our all-in-one platform.</p>
            <?php if (\App\Core\Auth::isLoggedIn()): ?>
                <?php if (\App\Core\Auth::isStaffOrAdmin()): ?>
                    <a href="<?= \App\Config\App::basePath() ?>/admin/dashboard"
                        class="btn btn-lg btn-secondary gap-2 shadow-lg">Go to Dashboard <i data-lucide="arrow-right"
                            class="w-5 h-5"></i></a>
                <?php else: ?>
                    <a href="<?= \App\Config\App::basePath() ?>/user/dashboard"
                        class="btn btn-lg btn-secondary gap-2 shadow-lg">Go to Dashboard <i data-lucide="arrow-right"
                            class="w-5 h-5"></i></a>
                <?php endif; ?>
            <?php else: ?>
                <button type="button" onclick="document.getElementById('loginModal').showModal()"
                    class="btn btn-lg btn-secondary gap-2 shadow-lg">
                    Get Started
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Stats Section -->
<div class="py-12 bg-base-200/60 border-y border-base-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="stats stats-vertical lg:stats-horizontal shadow-sm w-full bg-base-100 border border-base-200">
            <div class="stat">
                <div class="stat-figure text-primary">
                    <i data-lucide="store" class="w-8 h-8"></i>
                </div>
                <div class="stat-title font-semibold text-xs uppercase opacity-60">Total Stalls</div>
                <div class="stat-value text-primary"><?= (int)($stats['total_stalls'] ?? 0) ?></div>
                <div class="stat-desc">Commercial spaces in complex</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-success">
                    <i data-lucide="check-circle" class="w-8 h-8"></i>
                </div>
                <div class="stat-title font-semibold text-xs uppercase opacity-60">Available Stalls</div>
                <div class="stat-value text-success"><?= (int)($stats['available_stalls'] ?? 0) ?></div>
                <div class="stat-desc">Ready for immediate lease</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-secondary">
                    <i data-lucide="users" class="w-8 h-8"></i>
                </div>
                <div class="stat-title font-semibold text-xs uppercase opacity-60">Occupied Stalls</div>
                <div class="stat-value text-secondary"><?= (int)($stats['occupied_stalls'] ?? 0) ?></div>
                <div class="stat-desc">Active vendor operations</div>
            </div>
            
            <div class="stat">
                <div class="stat-figure text-info">
                    <i data-lucide="percent" class="w-8 h-8"></i>
                </div>
                <div class="stat-title font-semibold text-xs uppercase opacity-60">Occupancy Rate</div>
                <div class="stat-value text-info"><?= (int)($stats['occupancy_rate'] ?? 0) ?>%</div>
                <div class="stat-desc">Current capacity utilization</div>
            </div>
        </div>
    </div>
</div>
<!-- Features Section -->
<div class="py-20 bg-base-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-4xl font-extrabold">Platform Features</h2>
            <p class="mt-4 text-lg opacity-70">Everything you need to successfully manage your properties.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="card-body items-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-2">
                        <i data-lucide="layout-grid" class="w-7 h-7"></i>
                    </div>
                    <h3 class="card-title">Stall Allocation</h3>
                    <p class="opacity-70">Easily map, assign, and manage stall availability in real-time. Keep track of
                        occupied versus available spaces.</p>
                </div>
            </div>

            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="card-body items-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-success/10 text-success flex items-center justify-center mb-2">
                        <i data-lucide="credit-card" class="w-7 h-7"></i>
                    </div>
                    <h3 class="card-title">Automated Billing</h3>
                    <p class="opacity-70">Integrate directly with PayMongo for secure, automated payment collection and
                        tracking.</p>
                </div>
            </div>

            <div class="card bg-base-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="card-body items-center text-center">
                    <div
                        class="w-14 h-14 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center mb-2">
                        <i data-lucide="users" class="w-7 h-7"></i>
                    </div>
                    <h3 class="card-title">Tenant Portals</h3>
                    <p class="opacity-70">Dedicated dashboards for users to view assigned stalls, profile info, tickets,
                        and payment history.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Visual Floor Plan CTA Section -->
<div class="py-16 bg-base-100 border-t border-base-200">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="card bg-gradient-to-r from-primary/5 to-secondary/5 border border-base-200 shadow-sm">
            <div class="card-body py-10 items-center">
                <div class="w-14 h-14 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-3">
                    <i data-lucide="map" class="w-7 h-7"></i>
                </div>
                <h2 class="card-title text-2xl font-bold">Interactive Complex Directory Map</h2>
                <p class="text-sm opacity-70 max-w-md mb-6">Explore the visual directory map of our floor layouts. See vacant stalls, occupancy listings, and floor assignments online.</p>
                <?php if (\App\Core\Auth::isLoggedIn()): ?>
                    <a href="<?= \App\Config\App::basePath() ?>/user/stalls/map" class="btn btn-primary btn-sm gap-2">
                        <i data-lucide="compass" class="w-4 h-4"></i> View Complex Map
                    </a>
                <?php else: ?>
                    <button type="button" onclick="document.getElementById('loginModal').showModal()" class="btn btn-primary btn-sm gap-2">
                        <i data-lucide="compass" class="w-4 h-4"></i> View Complex Map
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Available Stalls Section -->
<?php if (!empty($availableStalls)): ?>
    <div class="py-20 bg-base-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-4xl font-extrabold">Available Stalls</h2>
                <p class="mt-4 text-lg opacity-70">Find the perfect space for your business today.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($availableStalls as $stall): ?>
                    <div class="card bg-base-100 shadow-md hover:shadow-lg transition-shadow">
                        <?php if (!empty($stall['image'])): ?>
                            <?php $stallImageUrl = \App\Config\App::path('/' . ltrim($stall['image'], '/')); ?>
                            <figure><img src="<?= htmlspecialchars($stallImageUrl) ?>"
                                    alt="Stall <?= htmlspecialchars($stall['stall_number']) ?>" class="w-full h-48 object-cover" />
                            </figure>
                        <?php else: ?>
                            <figure class="bg-base-300 h-48 flex items-center justify-center">
                                <i data-lucide="store" class="h-16 w-16 opacity-30"></i>
                            </figure>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="flex justify-between items-center gap-2">
                                <h3 class="card-title">Stall <?= htmlspecialchars($stall['stall_number']) ?></h3>
                                <div class="badge badge-success gap-1">Available</div>
                            </div>
                            <?php if (!empty($stall['location_name'])): ?>
                                <div class="badge badge-info badge-outline w-fit"><?= htmlspecialchars($stall['location_name']) ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-primary font-bold text-lg">PHP <?= number_format((float) $stall['price'], 2) ?> /
                                month</p>
                            <p class="opacity-60 text-sm line-clamp-3">
                                <?= htmlspecialchars($stall['description'] ?: 'No description provided.') ?>
                            </p>
                            <div class="card-actions justify-end mt-2">
                                <?php if (\App\Core\Auth::isLoggedIn()): ?>
                                    <a href="<?= \App\Config\App::basePath() ?>/user/dashboard" class="btn btn-primary btn-sm">View
                                        Dashboard</a>
                                <?php else: ?>
                                    <button type="button" onclick="document.getElementById('loginModal').showModal()"
                                        class="btn btn-primary btn-sm">Rent Now</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Footer -->
<footer class="footer footer-center p-10 bg-base-300 text-base-content">
    <aside>
        <div class="flex items-center gap-2 mb-2">
            <i data-lucide="store" class="w-6 h-6 text-primary"></i>
            <span class="text-lg font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <p class="opacity-60">Streamline your stall management operations.</p>
        <div class="flex items-center gap-4 mt-4 text-sm font-medium">
            <button type="button" onclick="openTermsModal()" class="hover:underline opacity-80">Terms & Conditions</button>
            <span class="opacity-30">|</span>
            <button type="button" onclick="openPrivacyModal()" class="hover:underline opacity-80">Privacy Policy</button>
        </div>
        <p class="mt-4 opacity-50">&copy; <?= date('Y') ?> <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?>. All
            rights reserved.</p>
    </aside>
</footer>

<?php if (!\App\Core\Auth::isLoggedIn()): ?>
    <!-- ===== LOGIN MODAL ===== -->
    <dialog id="loginModal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
            <div class="text-center mb-6">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <i data-lucide="store" class="w-7 h-7 text-primary"></i>
                    <span class="text-xl font-bold"><?= htmlspecialchars($appName) ?></span>
                </div>
                <h3 class="text-2xl font-extrabold">Sign in to your account</h3>
                <p class="mt-1 text-sm opacity-60">
                    Don't have an account?
                    <button type="button" class="link link-primary font-medium"
                        onclick="document.getElementById('loginModal').close(); document.getElementById('registerModal').showModal();">Sign
                        up here</button>
                </p>
            </div>
            <div id="login-error" class="hidden alert alert-error mb-4 text-sm py-2"></div>
            <form id="loginForm" action="<?= \App\Config\App::basePath() ?>/login" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Email address</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 opacity-50"></i>
                        <input id="modal-login-email" name="email" type="email" autocomplete="email" required placeholder="you@example.com"
                            class="grow" />
                    </label>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium" id="modal-login-password-label">Password</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4 opacity-50"></i>
                        <input name="password" type="password" autocomplete="current-password" required
                            placeholder="Enter your password" class="grow" />
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <label class="label cursor-pointer gap-2">
                        <input type="checkbox" name="remember-me" class="checkbox checkbox-primary checkbox-sm" />
                        <span class="label-text">Remember me</span>
                    </label>
                    <button type="button" class="link link-primary text-sm"
                        onclick="document.getElementById('loginModal').close(); document.getElementById('forgotPasswordModal').showModal();">Forgot
                        password?</button>
                </div>
                <button type="submit" class="btn btn-primary w-full">Sign in</button>
            </form>

            <div class="divider text-xs opacity-50 my-4">OR CONTINUE WITH</div>

            <div class="flex justify-center">
                <a href="<?= \App\Config\App::basePath() ?>/auth/google" class="btn btn-outline gap-2 btn-sm py-2 w-full">
                    <svg class="h-4 w-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    Google
                </a>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- ===== REGISTER MODAL ===== -->
    <dialog id="registerModal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
            <div class="text-center mb-6">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <i data-lucide="store" class="w-7 h-7 text-primary"></i>
                    <span class="text-xl font-bold"><?= htmlspecialchars($appName) ?></span>
                </div>
                <h3 class="text-2xl font-extrabold">Create an account</h3>
                <p class="mt-1 text-sm opacity-60">
                    Already have an account?
                    <button type="button" class="link link-primary font-medium"
                        onclick="document.getElementById('registerModal').close(); document.getElementById('loginModal').showModal();">Sign
                        in here</button>
                </p>
            </div>
            <div id="register-error" class="hidden alert alert-error mb-4 text-sm py-2"></div>
            <form id="registerForm" action="<?= \App\Config\App::basePath() ?>/register" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <input type="hidden" name="registration_action" value="start">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Full Name</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="user" class="w-4 h-4 opacity-50"></i>
                        <input name="name" type="text" autocomplete="name" required placeholder="John Doe" class="grow" />
                    </label>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Email address</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 opacity-50"></i>
                        <input name="email" type="email" autocomplete="email" required placeholder="you@example.com"
                            class="grow" />
                    </label>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Phone number</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="smartphone" class="w-4 h-4 opacity-50"></i>
                        <input name="phone" type="tel" autocomplete="tel" required placeholder="+63 917 123 4567"
                            class="grow" />
                    </label>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Password</span></label>
                    <input id="regPassword" name="password" type="password" required placeholder="At least 8 characters"
                        class="input input-bordered w-full" />
                    <div class="mt-2">
                        <progress id="regStrengthMeter" class="progress progress-error w-full" value="0"
                            max="100"></progress>
                        <span id="regStrengthLabel" class="text-xs opacity-70">Password strength: Too weak</span>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Confirm Password</span></label>
                    <input id="regConfirmPassword" name="password_confirmation" type="password" required
                        placeholder="Confirm your password" class="input input-bordered w-full" />
                    <p id="regMatchHint" class="text-xs mt-1 opacity-60">Re-enter the same password.</p>
                </div>
                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input name="terms" type="checkbox" required class="checkbox checkbox-primary checkbox-sm" />
                        <span class="label-text">I agree to the <button type="button" class="link link-primary font-medium" onclick="openTermsModal()">Terms & Conditions</button> and <button type="button" class="link link-primary font-medium" onclick="openPrivacyModal()">Privacy Policy</button></span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-full">Register</button>
            </form>

            <div class="divider text-xs opacity-50 my-4">OR CONTINUE WITH</div>

            <div class="flex justify-center">
                <a href="<?= \App\Config\App::basePath() ?>/auth/google" class="btn btn-outline gap-2 btn-sm py-2 w-full">
                    <svg class="h-4 w-4" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    Google
                </a>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <!-- ===== FORGOT PASSWORD MODAL ===== -->
    <dialog id="forgotPasswordModal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
            <div class="text-center mb-6">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <i data-lucide="store" class="w-7 h-7 text-primary"></i>
                    <span class="text-xl font-bold"><?= htmlspecialchars($appName) ?></span>
                </div>
                <h3 class="text-2xl font-extrabold">Forgot your password?</h3>
                <p class="mt-1 text-sm opacity-60">Enter your email and we'll send you a reset link.</p>
            </div>
            <div id="forgot-error" class="hidden alert alert-error mb-4 text-sm py-2"></div>
            <form id="forgotPasswordForm" action="<?= \App\Config\App::basePath() ?>/forgot-password" method="POST"
                class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Email address</span></label>
                    <label class="input input-bordered flex items-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4 opacity-50"></i>
                        <input name="email" type="email" autocomplete="email" required placeholder="you@example.com"
                            class="grow" />
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
                <div class="text-center">
                    <!-- <button type="button" class="link link-primary text-sm"
                        onclick="document.getElementById('forgotPasswordModal').close();
                        document.getElementById('loginModal').showModal();">Back to login</button> -->
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
<?php endif; ?>

<!-- ===== PRIVACY POLICY MODAL ===== -->
<dialog id="privacyModal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl max-h-[80vh]">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-2xl mb-4 pb-3 border-b border-base-200">Privacy Policy</h3>
        <div id="privacyContent" class="prose max-w-none overflow-y-auto">
            <div class="flex justify-center py-8"><span class="loading loading-spinner loading-md"></span></div>
        </div>
        <div class="modal-action">
            <form method="dialog"><button class="btn">Close</button></form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<!-- ===== TERMS & CONDITIONS MODAL ===== -->
<dialog id="termsModal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl max-h-[80vh]">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-2xl mb-4 pb-3 border-b border-base-200">Terms & Conditions</h3>
        <div id="termsContent" class="prose max-w-none overflow-y-auto">
            <div class="flex justify-center py-8"><span class="loading loading-spinner loading-md"></span></div>
        </div>
        <div class="modal-action">
            <form method="dialog"><button class="btn">Close</button></form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    const _basePath = '<?= \App\Config\App::basePath() ?>';
    let _privacyLoaded = false;
    let _termsLoaded = false;

    function openPrivacyModal() {
        const modal = document.getElementById('privacyModal');
        modal.showModal();
        if (!_privacyLoaded) {
            fetch(_basePath + '/privacy')
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const prose = doc.querySelector('.prose');
                    document.getElementById('privacyContent').innerHTML = prose ? prose.innerHTML : html;
                    _privacyLoaded = true;
                })
                .catch(() => {
                    document.getElementById('privacyContent').innerHTML = '<p class="text-error">Failed to load privacy policy.</p>';
                });
        }
    }

    function openTermsModal() {
        const modal = document.getElementById('termsModal');
        modal.showModal();
        if (!_termsLoaded) {
            fetch(_basePath + '/terms')
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const prose = doc.querySelector('.prose');
                    document.getElementById('termsContent').innerHTML = prose ? prose.innerHTML : html;
                    _termsLoaded = true;
                })
                .catch(() => {
                    document.getElementById('termsContent').innerHTML = '<p class="text-error">Failed to load terms and conditions.</p>';
                });
        }
    }

    // Register modal password strength
    (function () {
        const pw = document.getElementById('regPassword');
        const confirm = document.getElementById('regConfirmPassword');
        const meter = document.getElementById('regStrengthMeter');
        const label = document.getElementById('regStrengthLabel');
        const hint = document.getElementById('regMatchHint');
        if (!pw || !confirm || !meter) return;

        function getStrength(val) {
            let s = 0;
            if (val.length >= 8) s++;
            if (val.length >= 12) s++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) s++;
            if (/\d/.test(val)) s++;
            if (/[^A-Za-z0-9]/.test(val)) s++;
            if (!val) return { v: 0, t: 'progress-error', l: 'Password strength: Too weak' };
            if (s <= 2) return { v: 25, t: 'progress-error', l: 'Password strength: Weak' };
            if (s === 3) return { v: 55, t: 'progress-warning', l: 'Password strength: Fair' };
            if (s === 4) return { v: 80, t: 'progress-info', l: 'Password strength: Strong' };
            return { v: 100, t: 'progress-success', l: 'Password strength: Very strong' };
        }
        function updateStrength() {
            const st = getStrength(pw.value);
            meter.value = st.v; meter.className = 'progress w-full ' + st.t;
            label.textContent = st.l;
        }
        function checkMatch() {
            if (!confirm.value) { hint.textContent = 'Re-enter the same password.'; hint.className = 'text-xs mt-1 opacity-60'; return; }
            if (confirm.value === pw.value) { hint.textContent = 'Passwords match.'; hint.className = 'text-xs mt-1 text-success'; }
            else { hint.textContent = 'Passwords do not match.'; hint.className = 'text-xs mt-1 text-error'; }
        }
        pw.addEventListener('input', () => { updateStrength(); checkMatch(); });
        confirm.addEventListener('input', checkMatch);
    })();

    // AJAX Form Handling
    (function initAjaxForms() {
        const handleForm = (formId, errorId, successId = null) => {
            const form = document.getElementById(formId);
            const errorDiv = document.getElementById(errorId);
            const successDiv = successId ? document.getElementById(successId) : null;
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                errorDiv.classList.add('hidden');
                errorDiv.textContent = '';
                if (successDiv) {
                    successDiv.classList.add('hidden');
                    successDiv.textContent = '';
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Processing...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.status === 'redirect') {
                        window.location.href = data.target;
                    } else if (data.status === 'success') {
                        if (successDiv) {
                            successDiv.textContent = data.message;
                            successDiv.classList.remove('hidden');
                            form.reset();
                        } else {
                            window.location.reload();
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    } else if (data.status === 'error') {
                        errorDiv.textContent = data.message || 'An error occurred. Please try again.';
                        errorDiv.classList.remove('hidden');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                } catch (err) {
                    console.error('Submission error:', err);
                    errorDiv.textContent = 'A connection error occurred. Please check your internet and try again.';
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        };

        handleForm('loginForm', 'login-error');
        handleForm('registerForm', 'register-error');
        handleForm('forgotPasswordForm', 'forgot-error');
    })();

    (function autoOpenAuthModal() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('login')) {
            const loginModal = document.getElementById('loginModal');
            if (loginModal && typeof loginModal.showModal === 'function') {
                loginModal.showModal();
            }
        } else if (urlParams.has('register')) {
            const registerModal = document.getElementById('registerModal');
            if (registerModal && typeof registerModal.showModal === 'function') {
                registerModal.showModal();
            }
        }
    })();
</script>