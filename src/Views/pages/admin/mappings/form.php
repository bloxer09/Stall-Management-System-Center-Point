<?php use App\Services\RegistrationVerification; ?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css" rel="stylesheet">

<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/admin/mappings" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-3xl">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="form-control">
                    <label class="label" for="user_id"><span class="label-text font-medium">Tenant (User) <span class="text-error">*</span></span></label>
                    <select id="user_id" name="user_id" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= isset($mapping) && (int)$mapping['user_id'] === (int)$user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?><?php if (!empty($user['phone'])): ?> • <?= htmlspecialchars(RegistrationVerification::formatPhoneForDisplay((string) $user['phone'])) ?><?php endif; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label" for="stall_id"><span class="label-text font-medium">Stall <span class="text-error">*</span></span></label>
                    <select id="stall_id" name="stall_id" required>
                        <option value="">Select an available stall...</option>
                        <?php if (isset($mapping)): ?>
                            <option value="<?= $mapping['stall_id'] ?>" selected>
                                <?= htmlspecialchars($mapping['stall_number']) ?>
                                <?php if (!empty($mapping['location_name'])): ?> - <?= htmlspecialchars($mapping['location_name']) ?><?php endif; ?>
                                - PHP <?= number_format((float)$mapping['price'], 2) ?> (Currently Assigned)
                            </option>
                        <?php endif; ?>
                        <?php foreach ($stalls as $stall): ?>
                            <?php if (!isset($mapping) || (int)$mapping['stall_id'] !== (int)$stall['id']): ?>
                                <option value="<?= $stall['id'] ?>">
                                    <?= htmlspecialchars($stall['stall_number']) ?>
                                    <?php if (!empty($stall['location_name'])): ?> - <?= htmlspecialchars($stall['location_name']) ?><?php endif; ?>
                                    - PHP <?= number_format((float)$stall['price'], 2) ?>/month
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($stalls) && !isset($mapping)): ?>
                        <label class="label"><span class="label-text-alt text-error">No available stalls. <a href="<?= \App\Config\App::basePath() ?>/admin/stalls/create" class="link">Create one</a>.</span></label>
                    <?php endif; ?>
                </div>

                <div class="form-control">
                    <label class="label" for="contract"><span class="label-text font-medium">Lease Contract (PDF)</span></label>
                    <input type="file" name="contract" id="contract" accept="application/pdf" class="file-input file-input-bordered w-full" />
                    <?php if (!empty($mapping['contract_document'])): ?>
                        <label class="label"><span class="label-text-alt">Current: <a href="<?= \App\Config\App::basePath() ?>/files/contract/<?= $mapping['id'] ?>" target="_blank" class="link link-primary">View Document</a></span></label>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" name="remove_contract" value="1" class="checkbox checkbox-sm">
                            <span class="label-text">Remove current contract</span>
                        </label>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="start_date"><span class="label-text font-medium">Start Date <span class="text-error">*</span></span></label>
                        <input type="date" name="start_date" id="start_date" required value="<?= htmlspecialchars($mapping['start_date'] ?? date('Y-m-d')) ?>" class="input input-bordered" />
                    </div>
                    <div class="form-control">
                        <label class="label" for="end_date"><span class="label-text font-medium">End Date <span class="text-error">*</span></span></label>
                        <input type="date" name="end_date" id="end_date" required value="<?= htmlspecialchars($mapping['end_date'] ?? date('Y-m-d', strtotime('+1 year'))) ?>" class="input input-bordered" />
                    </div>
                </div>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input id="is_active" name="is_active" type="checkbox" <?= (!isset($mapping) || $mapping['is_active']) ? 'checked' : '' ?> class="checkbox checkbox-primary" />
                        <div>
                            <span class="label-text font-medium">Active Assignment</span>
                            <p class="text-xs opacity-50">Uncheck to deactivate (frees up the stall).</p>
                        </div>
                    </label>
                </div>

                <div class="divider"></div>
                <div class="flex justify-end gap-2">
                    <a href="<?= \App\Config\App::basePath() ?>/admin/mappings" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= $mapping ? 'Update Assignment' : 'Assign Stall' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#user_id', { placeholder: 'Search for a user...', allowEmptyOption: true, maxOptions: 200 });
new TomSelect('#stall_id', { placeholder: 'Search for a stall...', allowEmptyOption: true, maxOptions: 200 });
</script>

