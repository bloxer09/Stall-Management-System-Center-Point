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

                <label class="form-control w-full">
                    <span class="label-text font-bold">Renewal duration</span>
                    <select wire:model.live="renewalMonths" class="select select-bordered rounded-xl w-full min-h-11">
                        @foreach(range(1, 12) as $months)
                            <option value="{{ $months }}">{{ $months }} {{ $months === 1 ? 'month' : 'months' }}</option>
                        @endforeach
                    </select>
                </label>
                @error('renewalMonths') <p role="alert" class="text-error text-sm">{{ $message }}</p> @enderror
                @if($renewalMonths >= 1 && $renewalMonths <= 12)
                    <p class="text-sm">Requested end date: {{ $renewalLease->end_date->copy()->addMonthsNoOverflow($renewalMonths)->format('M d, Y') }}.<br>
                        Full renewal rent: ₱{{ number_format((float) \App\Services\LeaseService::calculateContractRent((string) ($renewalLease->agreed_monthly_rate ?? $renewalLease->stall->price), $renewalMonths), 2) }}.</p>
                @endif
                <p class="text-sm">The office reviews your request first. Pay the full renewal invoice after review to extend your lease.</p>

                <label class="form-control w-full">
                    <span class="label-text text-xs font-bold">Note to the office (optional)</span>
                    <textarea wire:model="renewalNote" rows="2" maxlength="500" class="textarea textarea-bordered rounded-xl text-sm"></textarea>
                </label>

                @error('renewal') <div class="alert alert-error rounded-2xl text-sm">{{ $message }}</div> @enderror

                <div class="modal-action">
                    <button type="button" wire:click="closeRenewal" class="btn btn-ghost rounded-xl font-bold">Cancel</button>
                    <button type="button" wire:click="submitRenewal" wire:loading.attr="disabled" wire:target="submitRenewal" class="btn btn-primary rounded-xl font-bold min-h-11">Send request</button>
                </div>
            </div>
            <div class="modal-backdrop" wire:click="closeRenewal"></div>
        </div>
    @endif
