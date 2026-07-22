<div class="space-y-4">
    <div>
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
        <p class="mt-1 text-sm opacity-60">Update the privacy policy that users see on the main website.</p>
    </div>
    <div class="card bg-base-100 shadow-sm max-w-4xl">
        <div class="card-body">
            <form action="<?= \App\Config\App::basePath() ?>/admin/settings/privacy" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label" for="privacy_policy"><span class="label-text font-medium">Policy Content (HTML Supported)</span></label>
                    <textarea id="privacy_policy" name="privacy_policy" rows="15" class="textarea textarea-bordered font-mono text-sm"><?= htmlspecialchars($policy) ?></textarea>
                    <label class="label"><span class="label-text-alt opacity-50">Use HTML tags like &lt;h1&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt; to format the policy.</span></label>
                </div>
                <div class="divider"></div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save Privacy Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>

