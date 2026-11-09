let progressTimer;
const progressBar = () => document.getElementById('page-progress-bar');

document.addEventListener('livewire:navigate', () => {
    clearTimeout(progressTimer);
    const bar = progressBar();
    if (!bar) return;
    bar.style.opacity = '1';
    bar.style.width = '30%';
    progressTimer = setTimeout(() => { if (bar.isConnected) bar.style.width = '70%'; }, 200);
});

document.addEventListener('livewire:navigated', () => {
    clearTimeout(progressTimer);
    const bar = progressBar();
    if (!bar) return;
    bar.style.width = '100%';
    progressTimer = setTimeout(() => { bar.style.opacity = '0'; bar.style.width = '0%'; }, 350);
});
