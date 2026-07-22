<?php $appName = \App\Config\App::name(); ?>
<div class="min-h-[80vh] flex flex-col justify-center py-12 px-4 bg-base-200">
    <div class="mx-auto w-full max-w-md">
        <div class="text-center mb-6">
            <div class="flex items-center justify-center gap-2 mb-2">
                <i data-lucide="shield-check" class="w-8 h-8 text-success"></i>
                <span class="text-2xl font-bold"><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h2 class="text-2xl font-extrabold">Official Receipt Verification</h2>
            <p class="text-sm opacity-60">Verified Document Authentication System</p>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body">
                <?php if (empty($payment)): ?>
                    <div class="alert alert-error text-sm">
                        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                        <span>Receipt record not found or invalid reference number.</span>
                    </div>
                <?php else: ?>
                    <div class="flex items-center gap-2 p-3 bg-success/10 text-success rounded-lg text-sm font-semibold mb-4">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        <span>Authentic Official Receipt</span>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Receipt Number</span>
                            <span class="font-mono font-bold">#<?= str_pad($payment['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Status</span>
                            <span class="badge badge-success badge-sm uppercase font-bold"><?= htmlspecialchars($payment['status']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Tenant Name</span>
                            <span class="font-semibold"><?= htmlspecialchars($payment['user_name']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Stall Allocation</span>
                            <span class="font-semibold">Stall <?= htmlspecialchars($payment['stall_number']) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Amount Paid</span>
                            <span class="font-bold text-primary">₱<?= number_format((float)$payment['amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Payment Method</span>
                            <span><?= htmlspecialchars(ucfirst($payment['payment_method'] ?: 'online')) ?></span>
                        </div>
                        <div class="flex justify-between border-b border-base-200 pb-2">
                            <span class="opacity-60">Reference ID</span>
                            <span class="font-mono text-xs opacity-80"><?= htmlspecialchars($payment['payment_reference'] ?: $payment['paymongo_id'] ?: 'N/A') ?></span>
                        </div>
                        <div class="flex justify-between pb-1">
                            <span class="opacity-60">Date Paid</span>
                            <span class="text-xs"><?= date('M d, Y h:i A', strtotime($payment['paid_at'] ?: $payment['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mt-6 text-center">
                    <a href="<?= \App\Config\App::basePath() ?>/" class="btn btn-outline btn-sm w-full">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</div>
