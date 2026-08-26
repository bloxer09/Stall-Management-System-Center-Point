<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">System Settings & Portal Policies</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage global platform branding, contact details, Privacy Policy, and Terms & Conditions</p>
        </div>
    </div>

    <form wire:submit.prevent="saveSettings" class="space-y-6">
        <!-- General Portal Configuration -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-6 space-y-4">
            <h2 class="text-base font-black text-base-content uppercase tracking-wider">General Platform Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Platform Name *</span></label>
                    <input type="text" wire:model="platform_name" required class="input input-bordered rounded-xl text-sm" placeholder="e.g. STALLM Commercial Platform" />
                    @error('platform_name') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Support Email *</span></label>
                    <input type="email" wire:model="support_email" required class="input input-bordered rounded-xl text-sm" placeholder="support@stallm.com" />
                    @error('support_email') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Support Phone</span></label>
                    <input type="text" wire:model="support_phone" class="input input-bordered rounded-xl text-sm" placeholder="09171112233" />
                    @error('support_phone') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Privacy Policy Editor -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-6 space-y-3">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-base-content uppercase tracking-wider">Privacy Policy</h2>
                    <p class="text-xs text-base-content/60">Displayed publicly on <a href="{{ route('privacy') }}" target="_blank" class="link link-primary font-bold">/privacy-policy</a></p>
                </div>
            </div>
            <div class="form-control">
                <textarea wire:model="privacy_policy" rows="10" required class="textarea textarea-bordered rounded-2xl text-sm font-mono leading-relaxed" placeholder="Enter privacy policy text..."></textarea>
                @error('privacy_policy') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Terms and Conditions Editor -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-6 space-y-3">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-base-content uppercase tracking-wider">Terms and Conditions</h2>
                    <p class="text-xs text-base-content/60">Displayed publicly on <a href="{{ route('terms') }}" target="_blank" class="link link-primary font-bold">/terms-and-conditions</a></p>
                </div>
            </div>
            <div class="form-control">
                <textarea wire:model="terms_conditions" rows="10" required class="textarea textarea-bordered rounded-2xl text-sm font-mono leading-relaxed" placeholder="Enter terms and conditions text..."></textarea>
                @error('terms_conditions') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="btn btn-primary rounded-xl font-bold px-8 shadow-md">
                Save System Settings
            </button>
        </div>
    </form>
</div>
