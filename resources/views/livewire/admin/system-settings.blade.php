<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">System Settings</h1>
            <p class="text-xs text-base-content/60 mt-1">Manage platform branding, complex address, contact info, and legal policies.</p>
        </div>
    </div>

    <form wire:submit.prevent="saveSettings" class="space-y-6">
        <!-- General Configuration -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-6 space-y-4">
            <h2 class="text-base font-black text-base-content uppercase tracking-wider">General Information &amp; Address</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Commercial Complex / Platform Name *</span></label>
                    <input type="text" wire:model="platform_name" required class="input input-bordered rounded-xl text-sm" placeholder="e.g. Center Point Commercial Complex" />
                    @error('platform_name') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Commercial Complex Address *</span></label>
                    <input type="text" wire:model="platform_address" required class="input input-bordered rounded-xl text-sm" placeholder="e.g. Pagadian City, Zamboanga del Sur" />
                    @error('platform_address') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Support Email *</span></label>
                    <input type="email" wire:model="support_email" required class="input input-bordered rounded-xl text-sm" placeholder="support@stallm.com" />
                    @error('support_email') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Support Phone Number</span></label>
                    <input type="text" wire:model="support_phone" class="input input-bordered rounded-xl text-sm" placeholder="09171112233" />
                    @error('support_phone') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- Landing Page Custom Background Image -->
        <div class="card bg-base-100 rounded-3xl border border-base-300 shadow-md p-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <h2 class="text-base font-black text-base-content uppercase tracking-wider">Landing Page Hero Background Image</h2>
                    <p class="text-xs text-base-content/60">Upload a custom storefront or building photo to feature on the public home page.</p>
                </div>
                @if($existing_landing_bg_image)
                    <button type="button" wire:click="removeLandingBg" wire:confirm="Are you sure you want to remove the custom landing background?" class="btn btn-xs btn-outline btn-error rounded-xl font-bold gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Remove Custom Image
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div class="form-control md:col-span-2">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Choose Background Image (Max 10MB)</span></label>
                    <input type="file" wire:model="landing_bg_image" accept="image/*" class="file-input file-input-bordered file-input-sm rounded-xl text-xs w-full" />
                    @error('landing_bg_image') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                    <span class="text-[11px] text-base-content/50 mt-1">Recommended: High-resolution landscape photo (1920x1080 or larger).</span>
                </div>

                <!-- Preview Area -->
                <div class="flex flex-col items-center justify-center p-3 bg-base-200/50 rounded-2xl border border-base-300">
                    @if($landing_bg_image)
                        <div class="relative w-full h-24 rounded-xl overflow-hidden shadow-xs border border-base-300">
                            <img src="{{ $landing_bg_image->temporaryUrl() }}" class="w-full h-full object-cover" alt="New Preview">
                        </div>
                        <span class="text-xs text-success font-bold mt-1.5">New Image Ready to Save</span>
                    @elseif($existing_landing_bg_image)
                        <div class="relative w-full h-24 rounded-xl overflow-hidden shadow-xs border border-base-300">
                            <img src="{{ asset('storage/' . $existing_landing_bg_image) }}" class="w-full h-full object-cover" alt="Current Background">
                        </div>
                        <span class="text-xs text-base-content/60 font-medium mt-1.5">Current Background Photo</span>
                    @else
                        <div class="w-full h-24 rounded-xl bg-gradient-to-br from-primary/20 via-base-300 to-base-200 flex items-center justify-center text-base-content/40 text-xs font-bold border border-base-300">
                            Default Gradient Active
                        </div>
                        <span class="text-xs text-base-content/50 font-medium mt-1.5">No custom image uploaded</span>
                    @endif
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
                <textarea wire:model="privacy_policy" rows="8" required class="textarea textarea-bordered rounded-2xl text-sm font-mono leading-relaxed" placeholder="Enter privacy policy text..."></textarea>
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
                <textarea wire:model="terms_conditions" rows="8" required class="textarea textarea-bordered rounded-2xl text-sm font-mono leading-relaxed" placeholder="Enter terms and conditions text..."></textarea>
                @error('terms_conditions') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary rounded-xl font-bold px-8 shadow-md">
                <span wire:loading wire:target="saveSettings" class="loading loading-spinner loading-xs mr-1"></span>
                Save System Settings
            </button>
        </div>
    </form>
</div>
