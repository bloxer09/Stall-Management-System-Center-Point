<?php
$appName = \App\Config\App::name();
$isLoggedIn = \App\Core\Auth::isLoggedIn();
$dashboardUrl = $isLoggedIn 
    ? (\App\Core\Auth::isStaffOrAdmin() ? '/admin/dashboard' : '/user/dashboard')
    : '/?login=1';
?>
<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars('Page Not Found - ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="<?= \App\Config\App::basePath() ?>/public/assets/css/output.css" rel="stylesheet" type="text/css" />
    <style>html { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }</style>
    <script>
        const LIGHT_THEME = 'corporate';
        const DARK_THEME = 'dark';

        function normalizeTheme(theme) {
            if (theme === DARK_THEME) {
                return DARK_THEME;
            }
            return LIGHT_THEME;
        }

        function getTheme() { return normalizeTheme(localStorage.getItem('stallm-theme')); }
        function setTheme(t) {
            const theme = normalizeTheme(t);
            localStorage.setItem('stallm-theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
        }
        setTheme(getTheme());
    </script>
</head>
<body class="min-h-screen bg-base-200/60">
    <main class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-lg bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-8 md:p-10 text-center items-center">
                <div class="w-20 h-20 rounded-full bg-warning/10 text-warning flex items-center justify-center text-5xl mb-4 font-bold">?</div>
                <h1 class="text-4xl font-extrabold text-base-content leading-none mb-2">404</h1>
                <h2 class="text-2xl font-bold text-base-content/90 mb-3">Page Not Found</h2>
                <p class="text-base-content/75 mb-6 text-sm max-w-sm">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>

                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <a class="btn btn-primary" id="goBackBtn" href="<?= \App\Config\App::path('/') ?>">Go Back</a>
                    <a class="btn btn-outline" href="<?= \App\Config\App::path($dashboardUrl) ?>">Go to Dashboard</a>
                    <a class="btn btn-ghost" href="<?= \App\Config\App::path('/') ?>">Home Page</a>
                </div>
            </div>
        </div>
    </main>
    <script>
        (function () {
            var backBtn = document.getElementById('goBackBtn');
            if (!backBtn) {
                return;
            }

            backBtn.addEventListener('click', function (event) {
                if (window.history.length > 1) {
                    event.preventDefault();
                    window.history.back();
                }
            });
        })();
    </script>
</body>
</html>
