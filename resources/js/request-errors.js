const registration = Symbol.for('center-point.request-errors');

function initializeRequestErrors() {
    if (!window.Livewire || window[registration]) return;
    window[registration] = true;
    let current;
    const submittedForms = new Map();

    // Livewire's form and loading directives can both disable the same control.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.getAttributeNames().some(name => name.startsWith('wire:submit'))) return;
        const id = form.closest('[wire\\:id]')?.getAttribute('wire:id');
        if (!id || submittedForms.has(form)) return;
        const controls = [...form.querySelectorAll('input, textarea, select, button')].map(element => ({
            element, disabled: element.disabled, readOnly: element.readOnly,
        }));
        submittedForms.set(form, { id, controls, focus: document.activeElement });
    }, true);

    function finishForms(ids, failed) {
        for (const [form, state] of submittedForms) {
            if (!ids.includes(state.id)) continue;
            if (failed) {
                for (const { element, disabled, readOnly } of state.controls) {
                    if (!element.isConnected) continue;
                    element.disabled = disabled;
                    if (readOnly !== undefined) element.readOnly = readOnly;
                }
            }
            submittedForms.delete(form);
        }
    }

    function dismiss() {
        current?.element.remove();
        current = undefined;
    }

    function messageFor(status, content) {
        if (content === null) return 'The connection failed. Check your connection and whether your submission completed before trying again.';
        if (status === 401 || status === 419) return 'Your session needs to be renewed. Sign in in a new tab, then check whether your submission completed before trying again. If this page still reports an expired session, copy your unfinished input before refreshing.';
        if (status === 400 || status === 422) return 'The request could not be accepted. Check your input before trying again.';
        if (status === 403) return 'You do not have permission to complete this request. Contact the administrator if you need access.';
        if (status === 404) return 'The requested resource could not be found. Check whether it is still available before trying again.';
        if (status === 429) return 'Too many requests were sent. Wait a moment before trying again.';
        return 'The server could not complete the request. Check whether your submission completed before trying again.';
    }

    window.Livewire.hook('request', ({ payload, fail, succeed }) => {
        const ids = JSON.parse(payload).components.map(component => JSON.parse(component.snapshot).memo.id);
        const previousFocus = [...submittedForms.values()].find(state => ids.includes(state.id))?.focus || document.activeElement;
        const dialog = previousFocus?.closest('dialog[open]') || document.querySelector('dialog[open]');

        fail(({ status, content, preventDefault }) => {
            preventDefault();
            finishForms(ids, true);
            dismiss();
            const element = document.createElement('div');
            element.dataset.requestError = '';
            element.setAttribute('role', 'alert');
            element.setAttribute('aria-atomic', 'true');
            element.className = 'alert alert-error flex flex-col sm:flex-row text-sm items-start gap-3';
            const text = document.createElement('p');
            text.className = 'min-w-0 flex-1 break-words';
            text.textContent = messageFor(status, content);
            element.append(text);

            if (status === 401 || status === 419) {
                const signIn = document.createElement('a');
                signIn.href = '/login';
                signIn.target = '_blank';
                signIn.rel = 'noopener noreferrer';
                signIn.className = 'link font-semibold py-2 min-h-11';
                signIn.textContent = 'Sign in in a new tab';
                element.append(signIn);
            }

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn btn-sm btn-ghost min-h-11';
            close.textContent = 'Dismiss';
            close.setAttribute('aria-label', 'Dismiss request error');
            close.addEventListener('click', () => {
                dismiss();
                if (previousFocus?.isConnected && !previousFocus.disabled) previousFocus.focus({ preventScroll: true });
            });
            element.append(close);

            if (dialog?.isConnected && dialog.open) {
                element.classList.add('mb-4');
                const form = previousFocus?.closest('form');
                (form && dialog.contains(form) ? form : dialog.querySelector('.modal-box') || dialog).prepend(element);
            } else {
                const main = document.querySelector('main');
                if (main) {
                    element.classList.add('mb-4');
                    main.prepend(element);
                } else {
                    element.classList.add('fixed', 'top-20', 'left-4', 'right-4', 'z-[9998]', 'w-auto', 'max-h-[70vh]', 'overflow-y-auto');
                    document.body.append(element);
                }
            }
            current = { element, ids };
        });

        succeed(() => {
            finishForms(ids, false);
            if (current?.ids.some(id => ids.includes(id))) dismiss();
        });
    });

    document.addEventListener('livewire:navigating', () => {
        dismiss();
        submittedForms.clear();
    });
}

document.addEventListener('livewire:init', initializeRequestErrors, { once: true });
initializeRequestErrors();
