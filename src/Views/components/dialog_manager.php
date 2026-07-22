<dialog id="app-dialog-modal" class="modal">
    <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary" data-dialog-icon-wrap>
                <span class="text-xl font-semibold leading-none" data-dialog-icon>?</span>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold" data-dialog-title>Confirm action</h3>
                <p class="mt-2 text-sm leading-6 opacity-80 whitespace-pre-wrap" data-dialog-message></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" data-dialog-cancel>Cancel</button>
            <button type="button" class="btn btn-primary" data-dialog-confirm>Continue</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button aria-label="Close dialog">close</button>
    </form>
</dialog>

<script>
    (function initAppDialog() {
        if (window.appDialog) {
            return;
        }

        var dialog = document.getElementById('app-dialog-modal');
        if (!dialog) {
            return;
        }

        var titleNode = dialog.querySelector('[data-dialog-title]');
        var messageNode = dialog.querySelector('[data-dialog-message]');
        var iconNode = dialog.querySelector('[data-dialog-icon]');
        var iconWrapNode = dialog.querySelector('[data-dialog-icon-wrap]');
        var cancelButton = dialog.querySelector('[data-dialog-cancel]');
        var confirmButton = dialog.querySelector('[data-dialog-confirm]');

        var activeResolve = null;
        var activeMode = 'confirm';

        var variants = {
            primary: {
                buttonClass: 'btn-primary',
                iconWrapClass: 'bg-primary/10 text-primary',
                icon: '?'
            },
            error: {
                buttonClass: 'btn-error',
                iconWrapClass: 'bg-error/10 text-error',
                icon: '!'
            },
            warning: {
                buttonClass: 'btn-warning',
                iconWrapClass: 'bg-warning/20 text-warning-content',
                icon: '!'
            },
            success: {
                buttonClass: 'btn-success',
                iconWrapClass: 'bg-success/10 text-success',
                icon: 'i'
            },
            info: {
                buttonClass: 'btn-info',
                iconWrapClass: 'bg-info/10 text-info',
                icon: 'i'
            }
        };

        var finish = function (value) {
            var resolve = activeResolve;
            activeResolve = null;

            if (dialog.open) {
                dialog.close();
            }

            if (resolve) {
                resolve(value);
            }
        };

        var applyButtonClass = function (variant) {
            confirmButton.classList.remove('btn-primary', 'btn-error', 'btn-warning', 'btn-success', 'btn-info');
            confirmButton.classList.add((variants[variant] || variants.primary).buttonClass);
        };

        var applyIconClass = function (variant) {
            iconWrapNode.className = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl';
            iconWrapNode.classList.add.apply(iconWrapNode.classList, (variants[variant] || variants.primary).iconWrapClass.split(' '));
        };

        var openDialog = function (options) {
            options = options || {};
            activeMode = options.mode || 'confirm';

            var variant = options.variant || (activeMode === 'alert' ? 'info' : 'primary');
            var title = options.title || (activeMode === 'alert' ? 'Notice' : 'Confirm action');
            var message = options.message || '';

            titleNode.textContent = title;
            messageNode.textContent = message;
            iconNode.textContent = options.icon || (variants[variant] || variants.primary).icon;
            applyButtonClass(variant);
            applyIconClass(variant);

            if (activeMode === 'alert') {
                cancelButton.classList.add('hidden');
                confirmButton.textContent = options.confirmLabel || 'OK';
            } else {
                cancelButton.classList.remove('hidden');
                cancelButton.textContent = options.cancelLabel || 'Cancel';
                confirmButton.textContent = options.confirmLabel || 'Continue';
            }

            if (activeResolve) {
                activeResolve(false);
                activeResolve = null;
            }

            return new Promise(function (resolve) {
                activeResolve = resolve;
                dialog.showModal();
                window.setTimeout(function () {
                    confirmButton.focus();
                }, 0);
            });
        };

        confirmButton.addEventListener('click', function () {
            finish(true);
        });

        cancelButton.addEventListener('click', function () {
            finish(false);
        });

        dialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            finish(false);
        });

        dialog.addEventListener('close', function () {
            if (activeResolve) {
                finish(false);
            }
        });

        window.appDialog = {
            alert: function (message, options) {
                options = options || {};
                options.mode = 'alert';
                options.message = message;
                return openDialog(options);
            },
            confirm: function (message, options) {
                options = options || {};
                options.mode = 'confirm';
                options.message = message;
                return openDialog(options);
            }
        };

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (!form.dataset.confirmMessage) {
                return;
            }

            if (form.dataset.confirmApproved === '1') {
                form.dataset.confirmApproved = '';
                return;
            }

            event.preventDefault();

            window.appDialog.confirm(form.dataset.confirmMessage, {
                title: form.dataset.confirmTitle || 'Confirm action',
                confirmLabel: form.dataset.confirmConfirmLabel || 'Continue',
                cancelLabel: form.dataset.confirmCancelLabel || 'Cancel',
                variant: form.dataset.confirmVariant || 'primary'
            }).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                form.dataset.confirmApproved = '1';
                form.submit();
            });
        }, true);
    })();
</script>
