<section class="space-y-6">
    <header>
        <h2 class="text-lg font-black text-base-content">
            {{ __('Update Password') }}
        </h2>
        <p class="mt-1 text-xs text-base-content/60">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="space-y-4">
        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase">{{ __('Current Password') }}</span></label>
            <input wire:model="current_password" type="password" required autocomplete="current-password" class="input input-bordered rounded-xl text-sm" />
            @error('current_password') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase">{{ __('New Password') }}</span></label>
            <input wire:model.live="password" type="password" required autocomplete="new-password" class="input input-bordered rounded-xl text-sm" />
            <x-password-strength model="password" />
            @error('password') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="form-control">
            <label class="label"><span class="label-text font-bold text-xs uppercase">{{ __('Confirm Password') }}</span></label>
            <input wire:model="password_confirmation" type="password" required autocomplete="new-password" class="input input-bordered rounded-xl text-sm" />
            @error('password_confirmation') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
            <div class="flex items-center gap-4">
                <button type="submit" class="btn btn-primary rounded-xl font-bold px-6 shadow-xs">
                    {{ __('Save Password') }}
                </button>

                @if (session('status') === 'password-updated')
                    <p class="text-xs text-success font-bold">{{ __('Password updated successfully.') }}</p>
                @endif
            </div>

            <button type="button" onclick="Livewire.dispatch('open-forgot-password-modal', { email: '{{ auth()->user()->email }}' })" class="text-xs text-primary font-bold hover:underline">
                Forgot current password? Recover via OTP
            </button>
        </div>
    </form>
</section>
