<div class="space-y-4">
    <div>
         <h1 class="text-2xl font-bold"><?= htmlspecialchars($title) ?></h1>
         <p class="mt-1 text-sm opacity-60">Update the terms and conditions that users see on the main website.</p>
    </div>
    <div class="card bg-base-100 shadow-sm max-w-4xl">
        <div class="card-body">
            <form action="<?= \App\Config\App::basePath() ?>/admin/settings/terms" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
                <div class="form-control">
                    <label class="label" for="terms_conditions"><span class="label-text font-medium">Terms Content (HTML Supported)</span></label>
                    <textarea id="terms_conditions" name="terms_conditions" rows="15" class="textarea textarea-bordered font-mono text-sm"><?= htmlspecialchars($terms) ?></textarea>
                    <label class="label"><span class="label-text-alt opacity-50">Use HTML tags like &lt;h1&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt; to format the terms and conditions.</span></label>
                </div>
                <div class="divider"></div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save Terms & Conditions</button>
                </div>
            </form>
        </div>
    </div>
</div>
