<div class="max-w-md mx-auto py-8">
    <div class="card bg-base-100 border border-base-300 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
        <div>
            <h1 class="text-2xl font-black tracking-tight">Two-step verification</h1>
            <p class="text-sm text-base-content/70 mt-1">Enter the 6-digit code sent to <strong>{{ $maskedPhone }}</strong>.</p>
        </div>

        @if($sendFailed)
            <div class="alert alert-error rounded-2xl text-sm">We couldn't send the code. Please try Resend.</div>
        @endif

        <form wire:submit="verify" class="space-y-3">
            <x-otp-countdown :expires-at="$codeExpiresAt" context="sign-in" />
            <input type="text" wire:model="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus
                placeholder="••••••"
                class="input input-bordered rounded-xl w-full text-center text-2xl font-black tracking-[0.5em] @error('code') input-error @enderror">
            @error('code') <span class="text-error text-xs block">{{ $message }}</span> @enderror
            <button type="submit" class="btn btn-primary rounded-xl font-bold w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="verify">Verify and continue</span>
                <span wire:loading wire:target="verify">Checking…</span>
            </button>
        </form>

        <div class="flex items-center justify-between text-xs font-bold">
            <button type="button" wire:click="resend" class="text-primary hover:underline">Resend code</button>
            <button type="button" wire:click="cancel" class="text-base-content/60 hover:text-error">Cancel and sign out</button>
        </div>
    </div>
</div>
