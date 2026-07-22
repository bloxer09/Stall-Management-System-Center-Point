<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/admin/stall-locations" class="btn btn-ghost btn-sm btn-circle">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>

    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="form-control">
                    <label class="label" for="name"><span class="label-text font-medium">Location Name <span
                                class="text-error">*</span></span></label>
                    <input type="text" id="name" name="name" required
                        value="<?= htmlspecialchars($location['name'] ?? '') ?>" class="input input-bordered" />
                </div>

                <!-- <div class="form-control">
                    <label class="label" for="description"><span
                            class="label-text font-medium">Description</span></label>
                    <textarea id="description" name="description" rows="3"
                        class="textarea textarea-bordered"><?= htmlspecialchars($location['description'] ?? '') ?></textarea>
                </div> -->

                <!-- <div class="form-control">
                    <label class="label" for="sort_order"><span class="label-text font-medium">Sort Order</span></label>
                    <input type="number" id="sort_order" name="sort_order" value="<?= htmlspecialchars((string) ($location['sort_order'] ?? 0)) ?>" class="input input-bordered" />
                </div> -->

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="grid_rows"><span class="label-text font-medium">Layout Grid Rows</span></label>
                        <input type="number" id="grid_rows" name="grid_rows" min="1" max="50" required
                            value="<?= htmlspecialchars((string) ($location['grid_rows'] ?? 6)) ?>" class="input input-bordered" />
                    </div>

                    <div class="form-control">
                        <label class="label" for="grid_cols"><span class="label-text font-medium">Layout Grid Columns</span></label>
                        <input type="number" id="grid_cols" name="grid_cols" min="1" max="50" required
                            value="<?= htmlspecialchars((string) ($location['grid_cols'] ?? 8)) ?>" class="input input-bordered" />
                    </div>
                </div>

                <div class="divider"></div>
                <div class="flex justify-end gap-2">
                    <a href="<?= \App\Config\App::basePath() ?>/admin/stall-locations" class="btn btn-ghost">Cancel</a>
                    <button type="submit"
                        class="btn btn-primary"><?= $location ? 'Update Location' : 'Create Location' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>