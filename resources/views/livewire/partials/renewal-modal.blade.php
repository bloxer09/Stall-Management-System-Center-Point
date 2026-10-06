    <!-- Modal: Lease Renewal Request -->
    @if($renewalLease)
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box rounded-3xl max-w-md space-y-4 relative">
                <button type="button" wire:click="closeRenewal" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeRenewal()" class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3">✕</button>
                <div>
                    <h3 class="font-black text-xl">Request lease renewal</h3>
                    <p class="text-xs text-base-content/60 mt-1">
                        Stall #{{ $renewalLease->stall->stall_number }} &middot; current lease ends {{ $renewalLease->end_date->format('M d, Y') }}
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    @foreach(['6m' => '+6 months', '1y' => '+1 year', 'custom' => 'Custom date'] as $value => $label)
                        <label class="btn btn-sm rounded-xl font-bold {{ $renewalOption === $value ? 'btn-primary' : 'btn-outline' }}">
                            <input type="radio" wire:model.live="renewalOption" value="{{ $value }}" class="hidden">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>

                @if($renewalOption === 'custom')
                    <label class="form-control w-full">
                        <span class="label-text text-xs font-bold">New end date</span>
                        <input type="date" wire:model="renewalCustomDate" min="{{ $renewalLease->end_date->copy()->addDay()->toDateString() }}" class="input input-bordered input-sm rounded-xl">
                    </label>
                @endif
                @error('renewalCustomDate') <span class="text-error text-xs">{{ $message }}</span> @enderror

                <label class="form-control w-full">
                    <span class="label-text text-xs font-bold">Note to the office (optional)</span>
                    <textarea wire:model="renewalNote" rows="2" maxlength="500" class="textarea textarea-bordered rounded-xl text-sm"></textarea>
                </label>

                @error('renewal') <div class="alert alert-error rounded-2xl text-sm">{{ $message }}</div> @enderror

                <div class="modal-action">
                    <button type="button" wire:click="closeRenewal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button type="button" wire:click="submitRenewal" wire:loading.attr="disabled" class="btn btn-primary rounded-xl font-bold">Send request</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeRenewal"></div>
        </div>
    @endif
