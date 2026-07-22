<?php
$flash = \App\Core\Session::getFlash();
if (!$flash) {
    return;
}

$flashType = strtolower(trim((string) ($flash['type'] ?? 'info')));
if (!in_array($flashType, ['success', 'warning', 'error', 'info'], true)) {
    $flashType = 'info';
}

$flashTitle = trim((string) ($flash['title'] ?? ''));
if ($flashTitle === '') {
    $flashTitle = 'Notice';
}

$flashMessage = trim((string) ($flash['message'] ?? ''));
$flashDuration = (int) ($flash['duration'] ?? 5000);
if ($flashDuration < 2500 || $flashDuration > 15000) {
    $flashDuration = 5000;
}

$flashToneClasses = [
    'info' => [
        'icon' => 'text-info bg-info/12 ring-1 ring-info/20',
        'progress' => 'progress-info',
    ],
    'success' => [
        'icon' => 'text-success bg-success/12 ring-1 ring-success/20',
        'progress' => 'progress-success',
    ],
    'warning' => [
        'icon' => 'text-warning bg-warning/15 ring-1 ring-warning/20',
        'progress' => 'progress-warning',
    ],
    'error' => [
        'icon' => 'text-error bg-error/12 ring-1 ring-error/20',
        'progress' => 'progress-error',
    ],
];

$tone = $flashToneClasses[$flashType];
?>
<div class="toast toast-top toast-end z-[80] p-4" aria-live="polite" aria-atomic="true">
    <section
        class="pointer-events-auto w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-base-200 bg-base-100 text-base-content shadow-xl transition-all duration-200 ease-out opacity-0 translate-x-6 scale-95"
        data-flash-toast
        data-duration="<?= $flashDuration ?>"
        role="status"
    >
        <div class="flex items-start gap-4 p-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl <?= htmlspecialchars($tone['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true">
                <?php if ($flashType === 'success'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                    </svg>
                <?php elseif ($flashType === 'warning'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86 2.11 18.02A2 2 0 0 0 3.84 21h16.32a2 2 0 0 0 1.73-2.98L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                <?php elseif ($flashType === 'error'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                    </svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8h.01M11 12h1v4h1m8-4A9 9 0 1 1 3 12a9 9 0 0 1 18 0Z" />
                    </svg>
                <?php endif; ?>
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-base font-semibold leading-5"><?= htmlspecialchars($flashTitle, ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($flashMessage !== ''): ?>
                    <p class="mt-1 text-sm leading-6 text-base-content/70 break-words"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-ghost btn-sm btn-circle -mt-1 -mr-1 shrink-0" data-flash-dismiss aria-label="Dismiss notification">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <progress
            class="progress <?= htmlspecialchars($tone['progress'], ENT_QUOTES, 'UTF-8') ?> h-1 w-full rounded-none"
            value="100"
            max="100"
            data-flash-progress
            aria-hidden="true"
        ></progress>
    </section>
</div>
<script>
    (function initFlashToast() {
        var toast = document.querySelector('[data-flash-toast]');
        if (!toast) {
            return;
        }

        var dismissButton = toast.querySelector('[data-flash-dismiss]');
        var progressBar = toast.querySelector('[data-flash-progress]');
        var duration = parseInt(toast.getAttribute('data-duration') || '5000', 10);
        if (!Number.isFinite(duration) || duration < 0) {
            duration = 5000;
        }

        var timerId = 0;
        var rafId = 0;
        var startAt = 0;

        var removeToast = function () {
            if (toast.dataset.state === 'closing') {
                return;
            }

            toast.dataset.state = 'closing';
            window.clearTimeout(timerId);

            if (rafId) {
                window.cancelAnimationFrame(rafId);
            }

            toast.classList.remove('opacity-100', 'translate-x-0', 'scale-100');
            toast.classList.add('opacity-0', 'translate-x-6', 'scale-95');

            window.setTimeout(function () {
                toast.remove();
            }, 200);
        };

        if (dismissButton) {
            dismissButton.addEventListener('click', removeToast);
        }

        if (progressBar) {
            var updateProgress = function (timestamp) {
                if (!startAt) {
                    startAt = timestamp;
                }

                var elapsed = timestamp - startAt;
                var remaining = Math.max(0, duration - elapsed);
                progressBar.value = (remaining / duration) * 100;

                if (remaining > 0 && toast.dataset.state !== 'closing') {
                    rafId = window.requestAnimationFrame(updateProgress);
                }
            };

            progressBar.value = 100;
            rafId = window.requestAnimationFrame(updateProgress);
        }

        window.requestAnimationFrame(function () {
            toast.classList.remove('opacity-0', 'translate-x-6', 'scale-95');
            toast.classList.add('opacity-100', 'translate-x-0', 'scale-100');
        });

        timerId = window.setTimeout(removeToast, duration);
    })();
</script>
