<div class="max-w-xl mx-auto space-y-6">
    <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 sm:p-8 shadow-md">
        <h1 class="text-2xl sm:text-3xl font-black text-base-content tracking-tight">Check application status</h1>
        <p class="mt-2 text-sm text-base-content/60">Enter the reference number from your confirmation email and the email address you applied with.</p>

        <form wire:submit="lookup" class="space-y-4 mt-6">
            <div>
                <label for="statusReference" class="label text-xs font-bold">Reference number</label>
                <input id="statusReference" type="text" wire:model="reference" maxlength="30" autocomplete="off" aria-invalid="{{ $errors->has('reference') ? 'true' : 'false' }}" @error('reference') aria-describedby="statusReference-error" @enderror class="input input-bordered w-full rounded-xl text-sm uppercase" placeholder="APP-2026-000123">
                @error('reference') <p id="statusReference-error" role="alert" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="statusEmail" class="label text-xs font-bold">Email address</label>
                <input id="statusEmail" type="email" wire:model="email" maxlength="120" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="statusEmail-error" @enderror class="input input-bordered w-full rounded-xl text-sm" placeholder="you@example.com">
                @error('email') <p id="statusEmail-error" role="alert" class="text-error text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <x-turnstile />
            <button type="submit" class="btn btn-primary rounded-xl font-bold w-full" wire:loading.attr="disabled" wire:target="lookup"><span wire:loading wire:target="lookup" class="loading loading-spinner loading-xs"></span>
                Check status
            </button>
        </form>
    </div>

    @if($result === false)
        <div class="alert alert-warning rounded-2xl text-sm" role="status">
            <span>No application matches that reference and email.</span>
        </div>
    @elseif(is_array($result))
        @php
            $badge = ['pending' => ['badge-warning', 'Pending review'], 'approved' => ['badge-success', 'Approved'], 'rejected' => ['badge-error', 'Rejected']][$result['status']] ?? ['badge-ghost', ucfirst($result['status'])];
        @endphp
        <div class="card bg-base-100 rounded-3xl border border-base-300 p-6 shadow-md space-y-4" role="status">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-mono text-sm font-bold text-base-content">{{ $result['reference'] }}</span>
                <span class="badge {{ $badge[0] }} font-bold">{{ $badge[1] }}</span>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-base-content/50 font-bold uppercase">Stall</dt><dd class="font-bold">{{ $result['stall'] ?? '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/50 font-bold uppercase">Submitted</dt><dd>{{ $result['submitted'] }}</dd></div>
                @if($result['reviewed'])
                    <div><dt class="text-xs text-base-content/50 font-bold uppercase">Reviewed</dt><dd>{{ $result['reviewed'] }}</dd></div>
                @endif
            </dl>
            @if($result['status'] === 'rejected' && $result['reason'])
                <div class="p-3 rounded-xl bg-error/10 border border-error/20 text-sm">
                    <div class="text-xs font-bold uppercase text-error">Reason</div>
                    <p class="mt-1 text-base-content/80">{{ $result['reason'] }}</p>
                </div>
            @elseif($result['status'] === 'approved')
                <p class="text-sm text-success font-semibold">Check your email for your account set-up link.</p>
            @else
                <p class="text-sm text-base-content/60">The office is reviewing your application. You will receive an email once it is decided.</p>
            @endif
        </div>
    @endif
</div>
