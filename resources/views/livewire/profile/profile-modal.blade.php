<div>
    <dialog class="modal profile-modal" wire:ignore.self data-client-modal aria-label="Account Profile"
        x-data="{ visible: $wire.entangle('isOpen'), tab: $wire.entangle('activeTab'), dismiss() { this.visible = false; this.$dispatch('auth-closed'); this.$wire.closeModal(); } }"
        :class="{ 'modal-open': visible }"
        @open-profile-modal.window="tab = $event.detail?.tab === 'password' ? 'password' : 'profile'; visible = true"
        @keydown.escape.window="if (visible && isTopModal($el)) dismiss()">
        <div class="modal-box max-w-lg p-0 bg-base-100 rounded-3xl border border-base-300 relative shadow-2xl">
            <div class="profile-modal-header relative shrink-0 p-6 pb-4 border-b border-base-300">
            <!-- Close button -->
            <button type="button" @click="dismiss()" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/60 hover:text-base-content" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Header -->
            <div>
                <h3 class="font-black text-xl text-base-content">Account Profile</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Manage your personal information and account security</p>
            </div>

            <!-- Tab Switcher -->
            <div class="tabs tabs-boxed bg-base-200/60 p-1 rounded-2xl flex mt-5">
                <button type="button" @click="tab = 'profile'; $refs.profileBody.scrollTop = 0" :aria-pressed="tab === 'profile'" class="tab flex-1 rounded-xl text-xs font-bold transition " :class="tab === 'profile' ? 'tab-active bg-primary text-primary-content shadow-xs' : 'text-base-content/70'">
                    Profile Details
                </button>
                <button type="button" @click="tab = 'password'; $refs.profileBody.scrollTop = 0" :aria-pressed="tab === 'password'" class="tab flex-1 rounded-xl text-xs font-bold transition " :class="tab === 'password' ? 'tab-active bg-primary text-primary-content shadow-xs' : 'text-base-content/70'">
                    Password &amp; Security
                </button>
            </div>
            </div>
            <div class="profile-modal-body p-6 pt-4" x-ref="profileBody">

            <!-- Profile Details Tab -->
            <div x-show="tab === 'profile'" wire:key="profile-details-panel">
            <form wire:submit.prevent="updateProfile" class="space-y-4">
                <!-- Avatar Section -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-3 bg-base-200/40 rounded-2xl border border-base-300">
                    <div class="relative shrink-0">
                        @if ($photo && !$errors->has('photo'))
                            <img src="{{ $photo->temporaryUrl() }}" alt="Profile avatar" class="w-16 h-16 rounded-full object-cover border-2 border-primary shadow-xs" />
                        @elseif ($currentPhoto)
                            <img src="{{ asset('storage/' . $currentPhoto) }}" x-data x-on:click="openImageLightbox($el.src)" alt="Profile avatar" class="w-16 h-16 rounded-full object-cover border-2 border-primary shadow-xs cursor-pointer hover:scale-105 transition" title="Click to view full image" />
                        @else
                            <div class="w-16 h-16 rounded-full bg-primary text-primary-content font-black text-xl flex items-center justify-center border-2 border-primary shadow-xs">
                                {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="w-full sm:flex-1 min-w-0">
                        <label for="profile-photo-input" class="label p-0 mb-2 font-semibold text-xs">
                            Choose avatar
                        </label>
                        <input type="file" wire:model="photo" id="profile-photo-input" class="file-input file-input-bordered file-input-sm w-full" accept="image/*" aria-invalid="{{ $errors->has('photo') ? 'true' : 'false' }}" @error('photo') aria-describedby="profile-photo-error" @enderror>
                        <p class="text-[10px] text-base-content/60 mt-1">PNG, JPG up to 2MB</p>
                        @error('photo') <span id="profile-photo-error" role="alert" class="text-xs text-error block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Name Field -->
                <x-form-input name="name" label="Full Name *" wire:model="name" autocomplete="name" required />

                <!-- Email Field (Readonly / Non-changeable) -->
                <div class="form-control">
                    <div class="flex justify-between items-center px-1">
                        <label for="profile-email" class="label"><span class="label-text font-bold text-xs uppercase">Email Address</span></label>
                        <span class="badge badge-neutral text-[10px] font-bold gap-1 opacity-80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Locked
                        </span>
                    </div>
                    <input id="profile-email" type="email" value="{{ $email }}" disabled readonly class="input input-bordered rounded-xl text-sm bg-base-200 text-base-content/60 cursor-not-allowed opacity-80" />
                    <span class="text-[10px] text-base-content/50 mt-1 px-1">Email address is permanent and cannot be modified.</span>
                </div>

                <!-- Phone Number -->
                <x-form-input name="phone" label="Phone Number" type="tel" wire:model="phone" autocomplete="tel" placeholder="09171234567" />

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                    <button type="button" @click="dismiss()" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button type="submit"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="btn btn-primary rounded-xl font-bold gap-2 shadow-xs">
                        <span wire:loading wire:target="updateProfile" class="loading loading-spinner loading-xs"></span>
                        Save Profile
                    </button>
                </div>
            </form>
            </div>

            <!-- Password & Security Tab -->
            <div x-show="tab === 'password'" x-cloak wire:key="profile-security-panel" class="space-y-4">
            @if(auth()->user()->isAdmin())
                <div class="p-4 rounded-2xl border border-base-300 bg-base-200/40 space-y-3" wire:key="profile-two-factor" x-data="{ showDisable: false, sending: false, codeSent: $wire.entangle('twoFactorCodeSent'), async sendCode() { if (this.sending) return; this.sending = true; try { await this.$wire.sendTwoFactorEnableCode(); } finally { this.sending = false; } } }" :aria-busy="sending">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="font-black text-sm text-base-content">Two-Factor Authentication</h4>
                            <p class="text-xs text-base-content/60 mt-0.5">Require a 6-digit SMS code after your password every time you sign in.</p>
                        </div>
                        <button type="button" role="switch" aria-label="Two-factor authentication" aria-checked="{{ auth()->user()->hasTwoFactorEnabled() ? 'true' : 'false' }}"
                            @if(auth()->user()->hasTwoFactorEnabled()) @click="showDisable = !showDisable"
                            @else @click="if (!codeSent) sendCode()" @disabled(blank(auth()->user()->phone)) :aria-disabled="sending || codeSent" @endif
                            class="shrink-0 min-w-14 min-h-11 flex items-center justify-center rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary">
                            <span class="w-12 h-7 p-1 rounded-full flex items-center {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-primary justify-end' : 'bg-base-content/30 justify-start' }}" aria-hidden="true">
                                <span class="w-5 h-5 rounded-full bg-base-100 shadow-sm"></span>
                            </span>
                        </button>
                    </div>

                    @if(auth()->user()->hasTwoFactorEnabled())
                        <div x-show="showDisable" x-cloak class="flex flex-col sm:flex-row gap-2">
                            <x-form-input name="twoFactorPassword" label="Current password to turn off two-factor authentication" wire:model="twoFactorPassword" type="password" :reveal-password="true" autocomplete="current-password" class="flex-1" />
                            <button type="button" wire:click="disableTwoFactor" wire:loading.attr="disabled" class="btn btn-outline btn-error btn-sm rounded-xl font-bold">Turn off</button>
                        </div>
                    @elseif(blank(auth()->user()->phone))
                        <p class="text-xs text-warning font-semibold">Add a phone number first in Profile Details. Codes are sent to that number.</p>
                    @else
                        <p x-show="!codeSent" class="text-xs text-base-content/70" role="status">
                            <span x-show="!sending">Off · Turn on to verify your phone ending in {{ substr(auth()->user()->phone, -4) }}.</span>
                            <span x-show="sending" x-cloak>Sending verification code...</span>
                        </p>
                        <div x-show="codeSent" x-cloak wire:key="profile-two-factor-verification" class="space-y-3">
                            <x-otp-countdown :expires-at="$codeExpiresAt" context="enrollment" />
                            <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                                <div class="flex-1 min-w-0">
                                    <label for="profile-two-factor-code" class="label pb-1"><span class="label-text font-semibold text-xs">6-digit verification code</span></label>
                                    <input id="profile-two-factor-code" class="input input-bordered w-full rounded-xl text-sm tracking-widest" wire:model="twoFactorCode" inputmode="numeric" maxlength="6" autocomplete="one-time-code" aria-invalid="{{ $errors->has('twoFactorCode') ? 'true' : 'false' }}" @error('twoFactorCode') aria-describedby="profile-two-factor-error" @enderror />
                                </div>
                                <button type="button" wire:click="confirmTwoFactor" wire:loading.attr="disabled" wire:target="confirmTwoFactor" :disabled="sending" class="btn btn-primary rounded-xl font-bold">Confirm</button>
                            </div>
                            <button type="button" @click="sendCode()" :aria-disabled="sending" class="text-xs text-primary font-bold hover:underline min-h-11">
                                <span x-show="!sending">Resend code</span><span x-show="sending" x-cloak role="status">Sending verification code...</span>
                            </button>
                        </div>
                        @error('twoFactorCode') <span id="profile-two-factor-error" role="alert" class="text-xs text-error block">{{ $message }}</span> @enderror
                    @endif
                </div>
            @endif
            <form wire:submit.prevent="updatePassword" class="space-y-4">
                <x-form-input name="current_password" label="Current Password *" type="password" :reveal-password="true" wire:model="current_password" autocomplete="current-password" placeholder="Enter your current password" required />

                <div class="form-control">
                    <x-form-input name="password" label="New Password *" type="password" :reveal-password="true" wire:model.live="password" autocomplete="new-password" placeholder="Create a strong password" required />
                    <x-password-strength model="password" />
                </div>

                <x-form-input name="password_confirmation" label="Confirm New Password *" type="password" :reveal-password="true" wire:model="password_confirmation" autocomplete="new-password" placeholder="Confirm your password" required />

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-base-200">
                    <button type="button" @click="dismiss(); $dispatch('open-forgot-password-modal')" class="text-xs text-primary font-bold hover:underline">
                        Forgot Password
                    </button>

                    <div class="flex gap-2">
                        <button type="button" @click="dismiss()" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit"
                            wire:loading.attr="disabled" wire:loading.class="opacity-60"
                            class="btn btn-primary rounded-xl font-bold gap-2 shadow-xs">
                            <span wire:loading wire:target="updatePassword" class="loading loading-spinner loading-xs"></span>
                            Update Password
                        </button>
                    </div>
                </div>
            </form>

            </div>
            </div>
        </div>
        <div class="modal-backdrop"><button type="button" @click="dismiss()" aria-label="Close profile dialog">close</button></div>
    </dialog>
</div>
