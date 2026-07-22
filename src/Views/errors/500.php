<?php
/** @var string $userMessage */
/** @var string $errorId */
/** @var string|null $debugDetails */
/** @var bool $showDebugDetails */
$appName = \App\Config\App::name();
?>
<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars('Something Went Wrong - ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="<?= \App\Config\App::basePath() ?>/public/assets/css/output.css" rel="stylesheet" type="text/css" />
    <style>html { font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }</style>
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
    </script>
</head>
<body class="min-h-screen bg-base-200/60">
    <main class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-2xl bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-8 md:p-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-11 h-11 rounded-full bg-error/10 text-error flex items-center justify-center text-2xl">!</div>
                    <h1 class="card-title text-3xl font-bold">An Error Has Occurred</h1>
                </div>
                <p class="text-base-content/80"><?= htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-sm text-base-content/60 mt-3">Reference ID: <?= htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-xs text-base-content/50 mt-1">Share this reference ID with support so the matching log entry can be found quickly.</p>

                <div class="card-actions mt-6 justify-start gap-3">
                    <a class="btn btn-primary" id="goBackBtn" href="<?= \App\Config\App::path('/') ?>">Go Back</a>
                    <a class="btn btn-outline" href="javascript:location.reload()">Try Again</a>
                    <a class="btn btn-ghost" href="<?= \App\Config\App::path('/') ?>">Home</a>
                </div>

                <?php if (!empty($showDebugDetails) && !empty($debugDetails)): ?>
                    <div class="mt-6">
                        <div class="text-sm font-semibold mb-2">Debug Details</div>
                        <pre class="bg-base-300 rounded-box p-4 text-xs overflow-auto leading-relaxed"><?= htmlspecialchars($debugDetails, ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                <?php endif; ?>
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
