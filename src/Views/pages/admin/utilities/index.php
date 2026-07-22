<?php $appName = \App\Config\App::name(); ?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="zap" class="w-7 h-7 text-primary"></i>
                Metered Utility Billing
            </h1>
            <p class="text-sm opacity-60">Log electricity and water sub-meter readings for stall tenant billing.</p>
        </div>
        <button onclick="document.getElementById('addUtilityModal').showModal()" class="btn btn-primary gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i> Log Meter Reading
        </button>
    </div>

    <div class="card bg-base-100 shadow-xl">
        <div class="card-body p-0">
            <?php if (empty($readings)): ?>
                <div class="text-center py-12 px-4">
                    <i data-lucide="droplet" class="w-12 h-12 mx-auto text-base-content/30 mb-3"></i>
                    <p class="text-lg font-semibold">No meter readings logged</p>
                    <p class="text-sm opacity-60 mb-4">Log sub-meter readings for electricity or water per stall.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Stall</th>
                                <th>Utility Type</th>
                                <th>Prev Reading</th>
                                <th>Curr Reading</th>
                                <th>Consumption</th>
                                <th>Rate/Unit</th>
                                <th>Total Amount</th>
                                <th>Reading Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($readings as $item): ?>
                                <?php $consumption = max(0, $item['current_reading'] - $item['previous_reading']); ?>
                                <tr class="hover">
                                    <td class="font-bold">Stall <?= htmlspecialchars($item['stall_number']) ?></td>
                                    <td>
                                        <?php if ($item['type'] === 'electricity'): ?>
                                            <span class="badge badge-warning badge-sm font-semibold gap-1"><i data-lucide="zap" class="w-3 h-3"></i> Electricity</span>
                                        <?php else: ?>
                                            <span class="badge badge-info badge-sm font-semibold gap-1"><i data-lucide="droplet" class="w-3 h-3"></i> Water</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-xs font-mono"><?= number_format($item['previous_reading'], 2) ?></td>
                                    <td class="text-xs font-mono font-bold"><?= number_format($item['current_reading'], 2) ?></td>
                                    <td class="text-xs font-semibold text-primary"><?= number_format($consumption, 2) ?> units</td>
                                    <td class="text-xs">₱<?= number_format($item['rate_per_unit'], 2) ?></td>
                                    <td class="text-xs font-bold text-success">₱<?= number_format($item['total_amount'], 2) ?></td>
                                    <td class="text-xs opacity-70"><?= date('M d, Y', strtotime($item['reading_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Log Meter Reading -->
<dialog id="addUtilityModal" class="modal">
    <div class="modal-box max-w-md">
        <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button></form>
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
            <i data-lucide="zap" class="w-5 h-5 text-primary"></i> Log Sub-Meter Reading
        </h3>
        
        <form action="<?= \App\Config\App::basePath() ?>/admin/utilities/store" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            
            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Select Stall</span></label>
                <select name="stall_id" required class="select select-bordered w-full">
                    <option value="" disabled selected>Choose a stall...</option>
                    <?php foreach ($stalls as $stall): ?>
                        <option value="<?= $stall['id'] ?>">Stall <?= htmlspecialchars($stall['stall_number']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Utility Type</span></label>
                <select name="type" required class="select select-bordered w-full">
                    <option value="electricity">Electricity (kWh)</option>
                    <option value="water">Water (cu.m)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Current Reading</span></label>
                    <input name="current_reading" type="number" step="0.01" required placeholder="0.00" class="input input-bordered w-full" />
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-medium">Rate per Unit (₱)</span></label>
                    <input name="rate_per_unit" type="number" step="0.01" required placeholder="12.00" class="input input-bordered w-full" />
                </div>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Reading Date</span></label>
                <input name="reading_date" type="date" required value="<?= date('Y-m-d') ?>" class="input input-bordered w-full" />
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary w-full">Save Meter Reading</button>
            </div>
        </form>
    </div>
</dialog>
