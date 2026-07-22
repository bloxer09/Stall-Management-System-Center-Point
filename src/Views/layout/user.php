<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((isset($title) ? $title : 'Dashboard') . ' - ' . \App\Config\App::name(), ENT_QUOTES, 'UTF-8') ?></title>
    <link href="<?= \App\Config\App::basePath() ?>/public/assets/css/output.css" rel="stylesheet" type="text/css" />
    <style>
        html { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: oklch(var(--bc) / 0.2); border-radius: 3px; }
    </style>
    <script>
        const LIGHT_THEME = 'corporate';
        const DARK_THEME = 'dark';

        function normalizeTheme(theme) {
            if (theme === DARK_THEME) {
                return DARK_THEME;
            }
            return LIGHT_THEME; // includes migrating old "cupcake" values
        }

        function getTheme() { return normalizeTheme(localStorage.getItem('stallm-theme')); }
        function setTheme(t) {
            const theme = normalizeTheme(t);
            localStorage.setItem('stallm-theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
        }
        setTheme(getTheme());
        function toggleTheme() { setTheme(getTheme() === LIGHT_THEME ? DARK_THEME : LIGHT_THEME); }
    </script>
</head>
<body class="min-h-screen">
    
    <div class="drawer lg:drawer-open">
        <input id="user-drawer" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex flex-col">
            
            <!-- Top Navbar -->
            <div class="navbar bg-base-100 border-b border-base-200 sticky top-0 z-30">
                <div class="flex-none lg:hidden">
                    <label for="user-drawer" class="btn btn-square btn-ghost drawer-button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
                <div class="flex-1">
                    <h1 class="text-lg font-semibold px-2"><?= isset($title) ? htmlspecialchars($title) : 'Dashboard' ?></h1>
                </div>
                <div class="flex-none gap-2">
                    <?php
                        $authUser = \App\Core\Auth::user();
                        $profilePictureUrl = !empty($authUser['profile_picture'])
                            ? \App\Config\App::path('/' . ltrim($authUser['profile_picture'], '/'))
                            : null;
                    ?>
                    <!-- Notifications Dropdown -->
                    <?php require __DIR__ . '/../components/notification_dropdown.php'; ?>
                    <label class="swap swap-rotate btn btn-ghost btn-circle">
                        <input type="checkbox" onclick="toggleTheme()" />
                        <svg class="swap-on fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,17.05a1,1,0,0,0,0,1.41l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
                        <svg class="swap-off fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Z"/></svg>
                    </label>
                    <div class="divider divider-horizontal m-0 w-0"></div>
                    <span class="text-sm opacity-70 hidden sm:inline-block">Hello, <?= htmlspecialchars($authUser['name']) ?></span>
                    <div class="avatar">
                        <div class="w-9 rounded-full border border-base-200 overflow-hidden bg-secondary text-secondary-content">
                            <?php if ($profilePictureUrl): ?>
                                <img src="<?= htmlspecialchars($profilePictureUrl) ?>" alt="Profile">
                            <?php else: ?>
                                <span class="text-sm font-bold flex w-full h-full items-center justify-center"><?= strtoupper(substr($authUser['name'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php require __DIR__ . '/../components/flash_toast.php'; ?>
            <?php require __DIR__ . '/../components/dialog_manager.php'; ?>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-6 bg-base-200/50">
                <!-- Upcoming Dues Banner -->
                <?php if (isset($upcomingDues) && !empty($upcomingDues)): ?>
                    <div class="alert alert-warning mb-4 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <div>
                            <h4 class="font-bold text-sm">Upcoming Payments</h4>
                            <ul class="mt-1 text-sm list-disc list-inside">
                                <?php foreach($upcomingDues as $due): ?>
                                    <li>Rent for <strong>Stall <?= htmlspecialchars($due['stall_number']) ?></strong> due on <strong><?= date('M d, Y', strtotime($due['next_due_date'])) ?></strong> (in <?= $due['days_left'] ?> days)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <?= isset($content) ? $content : '' ?>
            </main>
        </div>

        <div class="drawer-side z-40">
            <label for="user-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <?php require_once __DIR__ . '/../components/user_sidebar.php'; ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= \App\Config\App::basePath() ?>/public/assets/js/lucide.min.js"></script>
    <script src="<?= \App\Config\App::basePath() ?>/public/assets/js/chart.min.js"></script>
    <script>
        lucide.createIcons();

        function openCheckoutModal(stallId, stallNumber, price) {
            document.getElementById('checkoutStallId').value = stallId;
            document.getElementById('checkoutStallNumber').innerText = stallNumber;
            document.getElementById('checkoutPrice').innerText = price;
            document.getElementById('checkoutModal').showModal();
        }
        function closeCheckoutModal() {
            document.getElementById('checkoutModal').close();
        }
    </script>
</body>
</html>


