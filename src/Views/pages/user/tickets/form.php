<div class="space-y-4">
    <div class="flex items-center gap-4">
        <a href="<?= \App\Config\App::basePath() ?>/user/tickets" class="btn btn-ghost btn-sm btn-circle"><i data-lucide="arrow-left" class="w-5 h-5"></i></a>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
    </div>
    <div class="card bg-base-100 shadow-sm max-w-2xl">
        <div class="card-body">
            <form action="<?= \App\Config\App::basePath() ?>/user/tickets/create" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label" for="stall_id"><span class="label-text font-medium">Select Stall <span class="text-error">*</span></span></label>
                    <select id="stall_id" name="stall_id" required class="select select-bordered w-full">
                        <option value="">Select a stall...</option>
                        <?php foreach ($mappings as $map): ?>
                            <option value="<?= $map['stall_id'] ?>">Stall <?= htmlspecialchars($map['stall_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label" for="issue"><span class="label-text font-medium">Issue Description <span class="text-error">*</span></span></label>
                    <textarea id="issue" name="issue" rows="5" required class="textarea textarea-bordered" placeholder="Describe the maintenance issue or request..."></textarea>
                </div>
                <div class="divider"></div>
                <div class="flex justify-end gap-2">
                    <a href="<?= \App\Config\App::basePath() ?>/user/tickets" class="btn btn-ghost">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

