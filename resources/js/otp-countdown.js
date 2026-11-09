const deadlines = new WeakMap();

function updateCountdowns() {
    for (const element of document.querySelectorAll('[data-otp-countdown]')) {
        const expiresAt = Number(element.dataset.expiresAt);
        let state = deadlines.get(element);
        if (!state || state.expiresAt !== expiresAt) {
            state = {
                expiresAt,
                deadline: Date.now() + Math.max(0, expiresAt - Number(element.dataset.serverNow)) * 1000,
            };
            deadlines.set(element, state);
        }
        element.dataset.countdownReady = 'true';

        const remaining = Math.max(0, Math.ceil((state.deadline - Date.now()) / 1000));
        const formatted = `${Math.floor(remaining / 60).toString().padStart(2, '0')}:${(remaining % 60).toString().padStart(2, '0')}`;
        const timer = element.querySelector('[role="timer"]');
        const status = element.querySelector('[role="status"]');
        const message = remaining === 0 ? 'Code expired. Request a new code to continue.' : '';
        if (timer.textContent !== formatted) timer.textContent = formatted;
        if (status.textContent !== message) status.textContent = message;
        element.querySelector('[data-otp-active]').hidden = remaining === 0;
    }
}

// One ticker survives Livewire morphs and navigation; removed nodes are weakly held.
setInterval(updateCountdowns, 1000);
document.addEventListener('livewire:navigated', updateCountdowns);
document.addEventListener('visibilitychange', updateCountdowns);
new MutationObserver(updateCountdowns).observe(document.documentElement, { childList: true, subtree: true });
updateCountdowns();
