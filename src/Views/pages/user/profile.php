<?php use App\Services\RegistrationVerification; ?>
<?php
$profileFormAction = $profileFormAction ?? (\App\Config\App::basePath() . '/user/profile');
$profilePictureUrl = null;
if (!empty($profile['profile_picture'])) {
    $profilePictureUrl = \App\Config\App::path('/' . ltrim($profile['profile_picture'], '/'));
}
?>

<div class="space-y-4">
    <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>

    <div class="card bg-base-100 shadow-sm max-w-3xl">
        <div class="card-body">
            <form action="<?= htmlspecialchars($profileFormAction, ENT_QUOTES, 'UTF-8') ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">

                <div class="flex flex-col sm:flex-row gap-6 items-start sm:items-center">
                    <div class="avatar">
                        <div class="w-24 rounded-full border border-base-200 bg-base-200 overflow-hidden">
                            <?php if ($profilePictureUrl): ?>
                                <img src="<?= htmlspecialchars($profilePictureUrl) ?>" alt="Profile picture">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-2xl font-bold opacity-60">
                                    <?= strtoupper(substr($profile['name'] ?? 'U', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex-1 w-full space-y-3">
                        <div>
                            <label class="label" for="profile_picture"><span class="label-text font-medium">Profile Picture</span></label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/png,image/jpeg,image/webp,image/gif" class="file-input file-input-bordered w-full" />
                            <label class="label"><span class="label-text-alt opacity-60">Allowed: JPG, PNG, WEBP, GIF (max 2MB)</span></label>
                        </div>
                        <?php if (!empty($profile['profile_picture'])): ?>
                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" name="remove_profile_picture" value="1" class="checkbox checkbox-sm">
                                <span class="label-text">Remove current profile picture</span>
                            </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="name"><span class="label-text font-medium">Full Name</span></label>
                        <input type="text" id="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" class="input input-bordered bg-base-200/70 text-base-content/70 cursor-not-allowed" readonly disabled />
                    </div>
                    <div class="form-control">
                        <label class="label" for="email"><span class="label-text font-medium">Email</span></label>
                        <input type="email" id="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" class="input input-bordered bg-base-200/70 text-base-content/70 cursor-not-allowed" readonly disabled />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label" for="phone"><span class="label-text font-medium">Phone Number</span></label>
                        <input type="text" id="phone" value="<?= htmlspecialchars(!empty($profile['phone']) ? RegistrationVerification::formatPhoneForDisplay((string) $profile['phone']) : 'Not set') ?>" class="input input-bordered bg-base-200/70 text-base-content/70 cursor-not-allowed" readonly disabled />
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text font-medium">Verification Status</span></label>
                        <div class="flex flex-wrap gap-2 pt-3">
                            <span class="badge <?= !empty($profile['email_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Email <?= !empty($profile['email_verified_at']) ? 'Verified' : 'Pending' ?></span>
                            <span class="badge <?= !empty($profile['phone_verified_at']) ? 'badge-success' : 'badge-ghost' ?>">Phone <?= !empty($profile['phone_verified_at']) ? 'Verified' : 'Pending' ?></span>
                        </div>
                    </div>
                </div>
                <p class="text-sm opacity-60">Your name, email, and phone number are locked and cannot be changed from this page.</p>

                <div class="divider"></div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


