const managed = new Map();
const modalFocus = new WeakMap();
const closingModals = new WeakMap();
const modalBodies = new WeakMap();
let stack = [];
let queued = false;
let headingId = 0;

const focusable = (root) => [...root.querySelectorAll('button, a[href], input, select, textarea, summary, [tabindex]')]
    .filter((el) => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length && !el.closest('[inert]'));

const topModal = () => stack.at(-1);
window.isTopModal = (el) => el.closest('.modal') === topModal();

function restoreFocus(previousFocus) {
    if (previousFocus?.isConnected && (!topModal() || topModal().contains(previousFocus))) previousFocus.focus({ preventScroll: true });
}

function animateExitedModal(modal) {
    if (modalBodies.get(modal) !== document.body || document.querySelector('.modal-open') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const box = modal.querySelector('.modal-box')?.cloneNode(true);
    if (!box) return;
    for (const element of [box, ...box.querySelectorAll('*')]) {
        for (const attribute of [...element.attributes]) {
            if (/^(x-|wire:|@|:)/.test(attribute.name) || attribute.name === 'id') element.removeAttribute(attribute.name);
        }
    }
    const presentation = document.createElement('div');
    presentation.className = modal.className;
    presentation.classList.add('modal-open');
    presentation.dataset.modalExit = '';
    presentation.setAttribute('aria-hidden', 'true');
    presentation.inert = true;
    presentation.style.pointerEvents = 'none';
    presentation.append(box);
    document.body.append(presentation);
    box.animate([{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(6px)' }], { duration: 120, easing: 'ease-in' }).finished.catch(() => {}).finally(() => presentation.remove());
}

function dismissClientModal(modal, previousFocus) {
    const box = modal.querySelector('.modal-box');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!box || reducedMotion) { modal.close(); restoreFocus(previousFocus); return; }
    box.getAnimations().forEach((animation) => animation.cancel());
    const animation = box.animate([{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(6px)' }], { duration: 120, easing: 'ease-in' });
    const closing = { animation, previousFocus };
    closingModals.set(modal, closing);
    animation.finished.then(() => {
        if (closingModals.get(modal) !== closing) return;
        closingModals.delete(modal);
        modal.close();
        restoreFocus(previousFocus);
    }).catch(() => {});
}

function synchronizeModals() {
    queued = false;
    for (const [modal, previousFocus] of managed) {
        const active = modal.isConnected && (modal.classList.contains('modal-open') || (!modal.hasAttribute('data-client-modal') && modal.hasAttribute('open')));
        if (active) {
            if (modal instanceof HTMLDialogElement && modal.classList.contains('modal-open') && !modal.open) {
                const lastFocus = modalFocus.get(modal);
                modal.showModal();
                if (lastFocus?.isConnected && modal.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
            }
            continue;
        }
        managed.delete(modal);
        stack = stack.filter((item) => item !== modal);
        if (modal instanceof HTMLDialogElement && modal.open && modal.isConnected && modal.hasAttribute('data-client-modal')) dismissClientModal(modal, previousFocus);
        else {
            if (!modal.isConnected || (modal instanceof HTMLDialogElement && !modal.open)) animateExitedModal(modal);
            if (modal instanceof HTMLDialogElement && modal.open) modal.close();
            restoreFocus(previousFocus);
        }
    }

    for (const modal of document.querySelectorAll('.modal-open, dialog.modal[open]')) {
        if (modal.hasAttribute('data-modal-exit')) continue;
        if (managed.has(modal)) continue;
        if (modal.hasAttribute('data-client-modal') && !modal.classList.contains('modal-open')) continue;
        const closing = closingModals.get(modal);
        closingModals.delete(modal);
        closing?.animation.cancel();
        managed.set(modal, closing?.previousFocus || document.activeElement);
        modalBodies.set(modal, document.body);
        stack.push(modal);
        if (!modal.hasAttribute('aria-label') && !modal.hasAttribute('aria-labelledby')) {
            const heading = modal.querySelector('h1, h2, h3, h4');
            if (heading) {
                heading.id ||= `modal-heading-${++headingId}`;
                modal.setAttribute('aria-labelledby', heading.id);
            }
        }
        if (modal instanceof HTMLDialogElement) {
            if (!modal.open) modal.showModal();
        } else {
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.tabIndex = -1;
        }
        (focusable(modal)[0] || modal).focus();
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            modal.querySelector('.modal-box')?.animate([{ opacity: 0, transform: 'translateY(6px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 140, easing: 'ease-out' });
        }
    }

    if (document.body.classList.contains('overflow-y-hidden') !== (stack.length > 0)) {
        document.body.classList.toggle('overflow-y-hidden', stack.length > 0);
    }
}

function scheduleModals() {
    if (queued) return;
    queued = true;
    queueMicrotask(synchronizeModals);
}

document.addEventListener('keydown', (event) => {
    const modal = topModal();
    if (!modal || event.key !== 'Tab') return;
    const controls = focusable(modal);
    const first = controls[0];
    const last = controls.at(-1);
    if (!first) { event.preventDefault(); modal.focus(); return; }
    if (!modal.contains(document.activeElement) || (!event.shiftKey && document.activeElement === last)) {
        event.preventDefault(); (event.shiftKey ? last : first).focus();
    } else if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
    }
});

document.addEventListener('focusin', (event) => {
    const modal = topModal();
    if (modal?.contains(event.target)) modalFocus.set(modal, event.target);
    if (modal && !modal.contains(event.target)) (focusable(modal)[0] || modal).focus();
});

// Livewire controls dismissal so confirmation prompts and server state stay in sync.
document.addEventListener('cancel', (event) => {
    if (event.target.classList.contains('modal-open') || event.target.hasAttribute('data-client-modal')) event.preventDefault();
}, true);
document.addEventListener('close', scheduleModals, true);

const modalObserver = new MutationObserver(scheduleModals);
function initializeAccessibility() {
    modalObserver.disconnect();
    modalObserver.observe(document.body, {
        subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'open'],
    });
    synchronizeModals();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAccessibility, { once: true });
else initializeAccessibility();
document.addEventListener('livewire:navigated', initializeAccessibility);

function updateUsableViewport() {
    const viewport = window.visualViewport;
    document.documentElement.style.setProperty('--usable-viewport-height', `${viewport?.height || window.innerHeight}px`);
    document.documentElement.style.setProperty('--keyboard-offset', `${Math.max(0, window.innerHeight - (viewport?.height || window.innerHeight) - (viewport?.offsetTop || 0))}px`);
}
window.visualViewport?.addEventListener('resize', updateUsableViewport);
window.visualViewport?.addEventListener('scroll', updateUsableViewport);
window.addEventListener('resize', updateUsableViewport);
document.addEventListener('focusin', (event) => {
    if (event.target.matches('input, textarea, select') && window.visualViewport?.height < window.innerHeight - 100) {
        requestAnimationFrame(() => {
            const rect = event.target.getBoundingClientRect();
            if (rect.bottom > window.visualViewport.height || rect.top < 0) event.target.scrollIntoView({ block: 'nearest' });
        });
    }
});
updateUsableViewport();
