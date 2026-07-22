<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(isset($title) ? $title : \App\Config\App::name(), ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>html { font-family: 'Inter', sans-serif; }</style>
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
<body class="min-h-screen flex flex-col">
    
    <!-- Navbar -->
    <div class="navbar bg-base-100 border-b border-base-200 shadow-sm sticky top-0 z-50">
        <div class="navbar-start">
            <a href="<?= \App\Config\App::basePath() ?>/" class="btn btn-ghost text-xl font-bold gap-1">
                <i data-lucide="store" class="w-6 h-6 text-primary"></i>
                <?= htmlspecialchars(\App\Config\App::name(), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
        <div class="navbar-end gap-2">
            <!-- Theme Toggle -->
            <label class="swap swap-rotate btn btn-ghost btn-circle btn-sm">
                <input type="checkbox" onclick="toggleTheme()" />
                <svg class="swap-on fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,17.05a1,1,0,0,0,0,1.41l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
                <svg class="swap-off fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Z"/></svg>
            </label>
            <?php if (\App\Core\Auth::isLoggedIn()): ?>
                <?php
                    $authUser = \App\Core\Auth::user();
                    $profilePictureUrl = !empty($authUser['profile_picture'])
                        ? \App\Config\App::path('/' . ltrim($authUser['profile_picture'], '/'))
                        : null;
                ?>
                <?php if (\App\Core\Auth::isStaffOrAdmin()): ?>
                    <a href="<?= \App\Config\App::basePath() ?>/admin/dashboard" class="btn btn-ghost btn-sm">Admin Dashboard</a>
                <?php else: ?>
                    <a href="<?= \App\Config\App::basePath() ?>/user/dashboard" class="btn btn-ghost btn-sm">Dashboard</a>
                <?php endif; ?>
                <?php require __DIR__ . '/../components/notification_dropdown.php'; ?>
                <span class="text-sm opacity-60 hidden sm:inline">Hi, <?= htmlspecialchars($authUser['name']) ?></span>
                <div class="avatar">
                    <div class="w-8 rounded-full border border-base-200 overflow-hidden bg-primary text-primary-content">
                        <?php if ($profilePictureUrl): ?>
                            <img src="<?= htmlspecialchars($profilePictureUrl) ?>" alt="Profile">
                        <?php else: ?>
                            <span class="text-xs font-bold flex w-full h-full items-center justify-center"><?= strtoupper(substr($authUser['name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="<?= \App\Config\App::basePath() ?>/logout" class="btn btn-error btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <button type="button" onclick="if(document.getElementById('loginModal')){document.getElementById('loginModal').showModal();}else{window.location.href='<?= \App\Config\App::basePath() ?>/?login=1';}" class="btn btn-primary btn-sm">Sign In</button>
            <?php endif; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../components/flash_toast.php'; ?>
    <?php require __DIR__ . '/../components/dialog_manager.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow w-full">
        <?= isset($content) ? $content : '' ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>


