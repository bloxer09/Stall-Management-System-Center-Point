<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/admin/stalls" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="stall_number"><span class="label-text font-medium">Stall Number <span class="text-error">*</span></span></label>
                        <input type="text" name="stall_number" id="stall_number" required value="<?= htmlspecialchars($stall['stall_number'] ?? '') ?>" class="input input-bordered w-full" />
                    </div>
                    <div class="form-control">
                        <label class="label" for="price"><span class="label-text font-medium">Price (Monthly) <span class="text-error">*</span></span></label>
                        <label class="input input-bordered flex items-center gap-2 w-full">
                            <span class="opacity-50">PHP</span>
                            <input type="number" step="0.01" name="price" id="price" required value="<?= htmlspecialchars((string)($stall['price'] ?? '')) ?>" class="grow" />
                        </label>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="location_id"><span class="label-text font-medium">Location</span></label>
                    <select id="location_id" name="location_id" class="select select-bordered w-full">
                        <option value="">Unassigned</option>
                        <?php foreach (($locations ?? []) as $location): ?>
                            <option value="<?= $location['id'] ?>" <?= isset($stall['location_id']) && (int)$stall['location_id'] === (int)$location['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="label"><span class="label-text-alt opacity-60">Need more locations? <a href="<?= \App\Config\App::basePath() ?>/admin/stall-locations" class="link link-primary">Manage locations</a>.</span></label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="grid_row"><span class="label-text font-medium">Grid Row Position</span></label>
                        <input type="number" name="grid_row" id="grid_row" min="1" max="50" value="<?= htmlspecialchars((string)($stall['grid_row'] ?? '')) ?>" class="input input-bordered w-full" placeholder="Auto / Drag" />
                    </div>
                    <div class="form-control">
                        <label class="label" for="grid_col"><span class="label-text font-medium">Grid Column Position</span></label>
                        <input type="number" name="grid_col" id="grid_col" min="1" max="50" value="<?= htmlspecialchars((string)($stall['grid_col'] ?? '')) ?>" class="input input-bordered w-full" placeholder="Auto / Drag" />
                    </div>
                </div>

                <div class="form-control">
                    <label class="label" for="status"><span class="label-text font-medium">Status</span></label>
                    <select id="status" name="status" class="select select-bordered w-full">
                        <option value="available" <?= isset($stall['status']) && $stall['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="occupied" <?= isset($stall['status']) && $stall['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="maintenance" <?= isset($stall['status']) && $stall['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="form-control">
                    <label class="label" for="description"><span class="label-text font-medium">Description</span></label>
                    <textarea id="description" name="description" rows="3" class="textarea textarea-bordered"><?= htmlspecialchars($stall['description'] ?? '') ?></textarea>
                    <label class="label"><span class="label-text-alt opacity-50">Brief description of this stall's location or features.</span></label>
                </div>

                <div class="form-control">
                    <label class="label" for="image"><span class="label-text font-medium">Stall Image</span></label>
                    <?php if (!empty($stall['image'])): ?>
                        <?php $stallImageUrl = \App\Config\App::path('/' . ltrim($stall['image'], '/')); ?>
                        <img src="<?= htmlspecialchars($stallImageUrl) ?>" alt="Current" class="h-32 w-auto object-cover rounded-box mb-3 border border-base-200" />
                        <label class="label cursor-pointer justify-start gap-2">
                            <input type="checkbox" name="remove_image" value="1" class="checkbox checkbox-sm">
                            <span class="label-text">Remove current image</span>
                        </label>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/*" class="file-input file-input-bordered w-full" />
                    <label class="label"><span class="label-text-alt opacity-50">Upload an image to showcase this stall on the landing page.</span></label>
                </div>

                <div class="divider"></div>
                <div class="flex justify-end gap-2">
                    <a href="<?= \App\Config\App::basePath() ?>/admin/stalls" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary"><?= $stall ? 'Update Stall' : 'Create Stall' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

