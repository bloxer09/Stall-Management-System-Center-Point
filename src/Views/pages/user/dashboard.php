<div class="space-y-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <form action="<?= \App\Config\App::basePath() ?>/user/dashboard" method="GET" class="join w-full sm:w-auto">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy ?? 's.stall_number') ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sortOrder ?? 'ASC') ?>">
                <label class="input input-bordered input-sm join-item flex items-center gap-2 flex-1">
                    <i data-lucide="search" class="w-4 h-4 opacity-50"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Search stalls..." class="grow" />
                </label>
                <button type="submit" class="btn btn-sm join-item">Search</button>
                <?php if (!empty($search)): ?><a href="<?= \App\Config\App::basePath() ?>/user/dashboard" class="btn btn-sm btn-error btn-outline join-item">Clear</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th><a href="?sort=s.stall_number&order=<?= ($sortBy ?? '') === 's.stall_number' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Stall No. <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=l.name&order=<?= ($sortBy ?? '') === 'l.name' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Location <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=m.start_date&order=<?= ($sortBy ?? '') === 'm.start_date' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Start <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=m.end_date&order=<?= ($sortBy ?? '') === 'm.end_date' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">End <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=s.price&order=<?= ($sortBy ?? '') === 's.price' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Price/mo <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th><a href="?sort=m.is_active&order=<?= ($sortBy ?? '') === 'm.is_active' && ($sortOrder ?? '') === 'ASC' ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search ?? '') ?>" class="inline-flex items-center gap-1">Status <i data-lucide="chevrons-up-down" class="w-3 h-3 opacity-40"></i></a></th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mappings)): ?>
                        <tr><td colspan="7" class="text-center py-12 opacity-50"><?= !empty($search) ? 'No stalls match your search.' : 'No stall assignments. Contact administration.' ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($mappings as $mapping): ?>
                            <?php
                                $isActive = (bool)$mapping['is_active'];
                                $isPaid = in_array($mapping['stall_id'], $paidStallIds ?? []);
                                $today = new DateTime();
                                $end = new DateTime($mapping['end_date']);
                                $diff = $today->diff($end);
                                $expired = $today > $end;
                                $daysLeft = $expired ? -$diff->days : $diff->days;
                                if (!$isActive) { $badge = 'badge-ghost'; $label = 'Inactive'; }
                                elseif ($expired) { $badge = 'badge-error'; $label = 'Expired'; }
                                elseif ($daysLeft <= 30) { $badge = 'badge-warning'; $label = "Expires in $daysLeft day" . ($daysLeft !== 1 ? 's' : ''); }
                                else { $badge = 'badge-success'; $label = 'Active'; }
                            ?>
                            <tr class="hover">
                                <td class="font-medium"><?= htmlspecialchars($mapping['stall_number']) ?></td>
                                <td>
                                    <?php if (!empty($mapping['location_name'])): ?>
                                        <div class="badge badge-info badge-outline badge-sm"><?= htmlspecialchars($mapping['location_name']) ?></div>
                                    <?php else: ?>
                                        <span class="opacity-40">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm opacity-70"><?= date('M d, Y', strtotime($mapping['start_date'])) ?></td>
                                <td class="text-sm opacity-70"><?= date('M d, Y', strtotime($mapping['end_date'])) ?></td>
                                <td class="font-medium">PHP <?= number_format((float)$mapping['price'], 2) ?></td>
                                <td><div class="badge <?= $badge ?> badge-sm"><?= $label ?></div></td>
                                <td>
                                    <?php if (!$isActive): ?>
                                        <span class="opacity-30">-</span>
                                    <?php elseif ($isPaid): ?>
                                        <div class="badge badge-success badge-outline badge-sm gap-1"><i data-lucide="check-circle" class="w-3 h-3"></i> Paid</div>
                                    <?php else: ?>
                                        <button type="button" onclick="openCheckoutModal('<?= $mapping['stall_id'] ?>', '<?= htmlspecialchars($mapping['stall_number']) ?>', '<?= number_format((float)$mapping['price'], 2) ?>')" class="btn btn-primary btn-xs gap-1"><i data-lucide="credit-card" class="w-3 h-3"></i> Pay Now</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php require_once __DIR__ . '/../../components/pagination.php'; ?>
    </div>
</div>

<dialog id="checkoutModal" class="modal">
    <div class="modal-box max-h-[85vh] overflow-y-auto">
        <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-success/10 flex items-center justify-center"><i data-lucide="credit-card" class="h-7 w-7 text-success"></i></div>
        </div>
        <h3 class="font-bold text-lg text-center">Complete Payment</h3>
        <p class="text-center mt-2 opacity-70">Pay the monthly allocation fee for Stall <strong id="checkoutStallNumber"></strong>.</p>
        <p class="text-center text-2xl font-bold mt-3">PHP <span id="checkoutPrice"></span></p>
        <form action="<?= \App\Config\App::basePath() ?>/payment/checkout" method="POST" class="modal-action">
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <input type="hidden" name="stall_id" id="checkoutStallId" value="">
            <button type="button" onclick="closeCheckoutModal()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Proceed to Checkout</button>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

