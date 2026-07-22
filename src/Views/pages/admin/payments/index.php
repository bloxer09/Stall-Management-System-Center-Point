<div class="space-y-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <div class="flex flex-wrap gap-2">
            <form action="<?= \App\Config\App::basePath() ?>/admin/payments/generate-monthly" method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <button type="submit" class="btn btn-primary btn-sm gap-2"><i data-lucide="calendar-plus" class="h-4 w-4"></i> Generate Monthly Invoices</button>
            </form>
            <form action="<?= \App\Config\App::basePath() ?>/admin/payments/send-reminders" method="POST" class="inline">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <button type="submit" class="btn btn-warning btn-sm gap-2"><i data-lucide="mail" class="h-4 w-4"></i> Send Due Reminders</button>
            </form>
            <a href="<?= \App\Config\App::basePath() ?>/admin/payments/export?search=<?= urlencode($search ?? '') ?>&sort=<?= urlencode($sortBy) ?>&order=<?= urlencode($sortOrder) ?>" class="btn btn-outline btn-sm gap-2"><i data-lucide="download" class="h-4 w-4"></i> Export CSV</a>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= \App\Config\App::basePath() ?>/admin/payments" method="GET" class="join w-full sm:w-auto">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search payments..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/admin/payments" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th><a href="?sort=p.created_at&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Created <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.due_date&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Due Date <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.paid_at&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Paid At <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Tenant</th>
                        <th>Stall</th>
                        <th>Location</th>
                        <th><a href="?sort=p.amount&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Amount <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.status&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=p.payment_method&order=<?= $sortOrder==='ASC'?'DESC':'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Method <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="11" class="text-center py-12 opacity-50">No payment records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr class="hover">
                                <td class="text-sm opacity-70"><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                                <td class="text-sm"><?= !empty($payment['due_date']) ? date('M d, Y', strtotime($payment['due_date'])) : '<span class="opacity-40">N/A</span>' ?></td>
                                <td class="text-sm"><?= !empty($payment['paid_at']) ? date('M d, Y H:i', strtotime($payment['paid_at'])) : '<span class="opacity-40">Not paid</span>' ?></td>
                                <td class="font-medium"><?= htmlspecialchars($payment['user_name']) ?></td>
                                <td><div class="badge badge-ghost badge-sm"><?= htmlspecialchars($payment['stall_number']) ?></div></td>
                                <td><?= !empty($payment['location_name']) ? htmlspecialchars($payment['location_name']) : '<span class="opacity-40">Unassigned</span>' ?></td>
                                <td class="font-medium">PHP <?= number_format((float)$payment['amount'], 2) ?></td>
                                <td>
                                    <?php
                                        $badge = 'badge-ghost';
                                        if ($payment['status'] === 'paid') $badge = 'badge-success';
                                        if ($payment['status'] === 'failed') $badge = 'badge-error';
                                        if ($payment['status'] === 'pending') $badge = 'badge-warning';
                                    ?>
                                    <div class="badge <?= $badge ?> badge-sm"><?= ucfirst(htmlspecialchars($payment['status'])) ?></div>
                                </td>
                                <td>
                                    <?php
                                        $method = $payment['payment_method'] ?: (!empty($payment['paymongo_id']) ? 'online' : null);
                                        $methodBadge = 'badge-ghost';
                                        if ($method === 'office') $methodBadge = 'badge-info';
                                        if ($method === 'online') $methodBadge = 'badge-secondary';
                                    ?>
                                    <?php if ($method): ?>
                                        <div class="badge <?= $methodBadge ?> badge-sm"><?= ucfirst(htmlspecialchars($method)) ?></div>
                                    <?php else: ?>
                                        <span class="opacity-40">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-xs font-mono opacity-50"><?= htmlspecialchars($payment['payment_reference'] ?: $payment['paymongo_id'] ?: 'N/A') ?></td>
                                <td class="text-right">
                                    <?php if ($payment['status'] === 'paid'): ?>
                                        <a href="<?= \App\Config\App::basePath() ?>/payment/receipt/<?= $payment['id'] ?>" class="btn btn-ghost btn-xs" title="Download receipt">
                                            <i data-lucide="download" class="w-4 h-4"></i>
                                        </a>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs gap-1"
                                            data-office-payment-button
                                            data-payment-id="<?= (int)$payment['id'] ?>"
                                            data-tenant="<?= htmlspecialchars($payment['user_name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-stall="<?= htmlspecialchars($payment['stall_number'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-amount="<?= htmlspecialchars(number_format((float)$payment['amount'], 2), ENT_QUOTES, 'UTF-8') ?>"
                                            data-due-date="<?= htmlspecialchars(!empty($payment['due_date']) ? date('M d, Y', strtotime($payment['due_date'])) : 'N/A', ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <i data-lucide="building-2" class="w-4 h-4"></i>
                                            Record Office Payment
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php require_once __DIR__ . '/../../../components/pagination.php'; ?>
    </div>
</div>

<dialog id="officePaymentModal" class="modal">
    <div class="modal-box max-h-[85vh] overflow-y-auto">
        <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-info/10 flex items-center justify-center">
                <i data-lucide="building-2" class="h-7 w-7 text-info"></i>
            </div>
        </div>
        <h3 class="font-bold text-lg text-center">Record Office Payment</h3>
        <p class="text-center mt-2 opacity-70">Use this when the tenant pays directly at the office instead of through the online gateway.</p>

        <div class="mt-4 rounded-xl bg-base-200/70 p-4 text-sm space-y-1">
            <div><span class="font-medium">Tenant:</span> <span id="officePaymentTenant">-</span></div>
            <div><span class="font-medium">Stall:</span> <span id="officePaymentStall">-</span></div>
            <div><span class="font-medium">Amount:</span> PHP <span id="officePaymentAmount">0.00</span></div>
            <div><span class="font-medium">Due Date:</span> <span id="officePaymentDueDate">-</span></div>
        </div>

        <form action="<?= \App\Config\App::basePath() ?>/admin/payments/record-office" method="POST" class="mt-4 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <input type="hidden" name="payment_id" id="officePaymentId" value="">

            <div class="form-control">
                <label class="label" for="office_paid_at"><span class="label-text font-medium">Paid At <span class="text-error">*</span></span></label>
                <input type="datetime-local" name="paid_at" id="office_paid_at" required class="input input-bordered" />
            </div>

            <div class="form-control">
                <label class="label" for="office_reference"><span class="label-text font-medium">Official Receipt / Reference</span></label>
                <input type="text" name="payment_reference" id="office_reference" placeholder="Optional OR number or office receipt reference" class="input input-bordered" />
            </div>

            <div class="form-control">
                <label class="label" for="office_notes"><span class="label-text font-medium">Notes</span></label>
                <textarea name="payment_notes" id="office_notes" rows="3" placeholder="Optional notes about the office payment" class="textarea textarea-bordered"></textarea>
            </div>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('officePaymentModal').close()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Office Payment</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

<script>
    (function initOfficePaymentModal() {
        var modal = document.getElementById('officePaymentModal');
        if (!modal) {
            return;
        }

        var paymentIdInput = document.getElementById('officePaymentId');
        var paidAtInput = document.getElementById('office_paid_at');
        var referenceInput = document.getElementById('office_reference');
        var notesInput = document.getElementById('office_notes');
        var tenantText = document.getElementById('officePaymentTenant');
        var stallText = document.getElementById('officePaymentStall');
        var amountText = document.getElementById('officePaymentAmount');
        var dueDateText = document.getElementById('officePaymentDueDate');

        var nowLocal = function () {
            var now = new Date();
            var pad = function (value) { return String(value).padStart(2, '0'); };
            return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
        };

        document.querySelectorAll('[data-office-payment-button]').forEach(function (button) {
            button.addEventListener('click', function () {
                paymentIdInput.value = button.getAttribute('data-payment-id') || '';
                tenantText.textContent = button.getAttribute('data-tenant') || '-';
                stallText.textContent = button.getAttribute('data-stall') || '-';
                amountText.textContent = button.getAttribute('data-amount') || '0.00';
                dueDateText.textContent = button.getAttribute('data-due-date') || '-';
                paidAtInput.value = nowLocal();
                if (referenceInput) {
                    referenceInput.value = '';
                }
                if (notesInput) {
                    notesInput.value = '';
                }
                modal.showModal();
            });
        });
    })();
</script>

