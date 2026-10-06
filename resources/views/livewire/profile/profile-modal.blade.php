<div>
    @if($isOpen)
    <dialog class="modal modal-open backdrop-blur-xs z-50">
        <div class="modal-box max-w-lg p-6 bg-base-100 rounded-3xl border border-base-300 relative shadow-2xl space-y-5">
            <!-- Close button -->
            <button wire:click="closeModal" class="btn btn-sm btn-circle btn-ghost absolute right-4 top-4 text-base-content/60 hover:text-base-content" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeModal()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <!-- Header -->
            <div>
                <h3 class="font-black text-xl text-base-content">Account Profile</h3>
                <p class="text-xs text-base-content/60 mt-0.5">Manage your personal information and account security</p>
            </div>

            <!-- Tab Switcher -->
            <div class="tabs tabs-boxed bg-base-200/60 p-1 rounded-2xl flex">
                <button type="button" wire:click="$set('activeTab', 'profile')" class="tab flex-1 rounded-xl text-xs font-bold transition {{ $activeTab === 'profile' ? 'tab-active bg-primary text-primary-content shadow-xs' : 'text-base-content/70' }}">
                    Profile Details
                </button>
                <button type="button" wire:click="$set('activeTab', 'password')" class="tab flex-1 rounded-xl text-xs font-bold transition {{ $activeTab === 'password' ? 'tab-active bg-primary text-primary-content shadow-xs' : 'text-base-content/70' }}">
                    Password &amp; Security
                </button>
            </div>

            <!-- Profile Details Tab -->
            @if($activeTab === 'profile')
            <form wire:submit.prevent="updateProfile" class="space-y-4">
                <!-- Avatar Section -->
                <div class="flex items-center gap-4 p-3 bg-base-200/40 rounded-2xl border border-base-300">
                    <div class="relative">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border-2 border-primary shadow-xs" />
                        @elseif ($currentPhoto)
                            <img src="{{ asset('storage/' . $currentPhoto) }}" onclick="openImageLightbox(this.src)" class="w-16 h-16 rounded-full object-cover border-2 border-primary shadow-xs cursor-pointer hover:scale-105 transition" title="Click to view full image" />
                        @else
                            <div class="w-16 h-16 rounded-full bg-primary text-primary-content font-black text-xl flex items-center justify-center border-2 border-primary shadow-xs">
                                {{ strtoupper(substr($name ?: 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <label for="profile-photo-input" class="btn btn-outline btn-xs rounded-xl font-bold cursor-pointer">
                            Upload Avatar
                        </label>
                        <input type="file" wire:model="photo" id="profile-photo-input" class="hidden" accept="image/*">
                        <p class="text-[10px] text-base-content/60 mt-1">PNG, JPG up to 2MB</p>
                        @error('photo') <span class="text-xs text-error block mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Name Field -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Full Name *</span></label>
                    <input wire:model="name" type="text" required class="input input-bordered rounded-xl text-sm" />
                    @error('name') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Email Field (Readonly / Non-changeable) -->
                <div class="form-control">
                    <div class="flex justify-between items-center px-1">
                        <label class="label"><span class="label-text font-bold text-xs uppercase">Email Address</span></label>
                        <span class="badge badge-neutral text-[10px] font-bold gap-1 opacity-80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Locked
                        </span>
                    </div>
                    <input type="email" value="{{ $email }}" disabled readonly class="input input-bordered rounded-xl text-sm bg-base-200 text-base-content/60 cursor-not-allowed opacity-80" />
                    <span class="text-[10px] text-base-content/50 mt-1 px-1">Email address is permanent and cannot be modified.</span>
                </div>

                <!-- Phone Number -->
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Phone Number</span></label>
                    <input wire:model="phone" type="text" placeholder="09171234567" class="input input-bordered rounded-xl text-sm" />
                    @error('phone') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button type="submit"
                        wire:loading.attr="disabled" wire:loading.class="opacity-60"
                        class="btn btn-primary rounded-xl font-bold gap-2 shadow-xs">
                        <span wire:loading wire:target="updateProfile" class="loading loading-spinner loading-xs"></span>
                        Save Profile
                    </button>
                </div>
            </form>
            @endif

            <!-- Password & Security Tab -->
            @if($activeTab === 'password')
            <form wire:submit.prevent="updatePassword" class="space-y-4">
                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Current Password *</span></label>
                    <input wire:model="current_password" type="password" required class="input input-bordered rounded-xl text-sm" />
                    @error('current_password') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">New Password *</span></label>
                    <input wire:model.live="password" type="password" required placeholder="Minimum 8 characters" class="input input-bordered rounded-xl text-sm" />
                    <x-password-strength model="password" />
                    @error('password') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label"><span class="label-text font-bold text-xs uppercase">Confirm New Password *</span></label>
                    <input wire:model="password_confirmation" type="password" required class="input input-bordered rounded-xl text-sm" />
                    @error('password_confirmation') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-base-200">
                    <button type="button" onclick="Livewire.dispatch('open-forgot-password-modal', { email: '{{ auth()->user()->email }}' })" class="text-xs text-primary font-bold hover:underline">
                        Forgot Password? Recover with OTP
                    </button>

                    <div class="flex gap-2">
                        <button type="button" wire:click="closeModal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                        <button type="submit"
                            wire:loading.attr="disabled" wire:loading.class="opacity-60"
                            class="btn btn-primary rounded-xl font-bold gap-2 shadow-xs">
                            <span wire:loading wire:target="updatePassword" class="loading loading-spinner loading-xs"></span>
                            Update Password
                        </button>
                    </div>
                </div>
            </form>

            @if(auth()->user()->isAdmin())
                <div class="p-4 rounded-2xl border border-base-300 bg-base-200/40 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="font-black text-sm text-base-content">Two-Factor Authentication</h4>
                            <p class="text-xs text-base-content/60 mt-0.5">Require a 6-digit SMS code after your password every time you sign in.</p>
                        </div>
                        @if(auth()->user()->hasTwoFactorEnabled())
                            <span class="badge badge-success badge-sm font-bold whitespace-nowrap">On</span>
                        @else
                            <span class="badge badge-ghost badge-sm font-bold whitespace-nowrap">Off</span>
                        @endif
                    </div>

                    @if(auth()->user()->hasTwoFactorEnabled())
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input wire:model="twoFactorPassword" type="password" placeholder="Current password to turn off" autocomplete="current-password" class="input input-bordered input-sm rounded-xl text-sm flex-1" />
                            <button type="button" wire:click="disableTwoFactor" wire:loading.attr="disabled" class="btn btn-outline btn-error btn-sm rounded-xl font-bold">Turn off</button>
                        </div>
                        @error('twoFactorPassword') <span class="text-xs text-error">{{ $message }}</span> @enderror
                    @elseif(blank(auth()->user()->phone))
                        <p class="text-xs text-warning font-semibold">Add a phone number first in Profile Details. Codes are sent to that number.</p>
                    @elseif(!$twoFactorCodeSent)
                        <button type="button" wire:click="sendTwoFactorEnableCode" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-xl font-bold">
                            <span wire:loading wire:target="sendTwoFactorEnableCode" class="loading loading-spinner loading-xs"></span>
                            Turn on &mdash; send code to {{ auth()->user()->phone }}
                        </button>
                        @error('twoFactorCode') <span class="text-xs text-error block">{{ $message }}</span> @enderror
                    @else
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input wire:model="twoFactorCode" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6-digit code" class="input input-bordered input-sm rounded-xl text-sm tracking-widest flex-1" />
                            <button type="button" wire:click="confirmTwoFactor" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-xl font-bold">Confirm</button>
                        </div>
                        @error('twoFactorCode') <span class="text-xs text-error">{{ $message }}</span> @enderror
                        <button type="button" wire:click="sendTwoFactorEnableCode" class="text-xs text-primary font-bold hover:underline">Resend code</button>
                    @endif
                </div>
            @endif
            @endif
        </div>
        <form method="dialog" class="modal-backdrop"><button wire:click="closeModal">close</button></form>
    </dialog>
    @endif
</div>
