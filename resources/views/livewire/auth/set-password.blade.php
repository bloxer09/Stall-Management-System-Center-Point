<div class="max-w-md mx-auto py-8">
    <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm">
        @if($linkValid)
            <h1 class="text-2xl font-black tracking-tight">Set your password</h1>
            <p class="text-xs text-base-content/60 mt-1">Choose a password for your new tenant account. You will be signed in right after.</p>

            <form wire:submit="save" class="space-y-4 mt-6">
                <label class="form-control w-full">
                    <span class="label-text text-xs font-bold">New password</span>
                    <input type="password" wire:model="password" autocomplete="new-password" class="input input-bordered rounded-xl w-full @error('password') input-error @enderror">
                    @error('password') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </label>
                <x-password-strength model="password" />
                <label class="form-control w-full">
                    <span class="label-text text-xs font-bold">Confirm password</span>
                    <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="input input-bordered rounded-xl w-full">
                </label>
                <button type="submit" class="btn btn-primary rounded-xl font-bold w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save password and sign in</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </form>
        @else
            <h1 class="text-2xl font-black tracking-tight">This link has expired</h1>
            <p class="text-sm text-base-content/70 mt-2">
                Password links work once and only for 72 hours. Use <strong>Forgot Password</strong> on the sign-in screen to get a new code.
            </p>
            <button type="button" x-data x-on:click="Livewire.dispatch('open-login-modal')" class="btn btn-primary rounded-xl font-bold w-full mt-6">Go to sign in</button>
        @endif
    </div>
</div>
