@php
    $statusStyles = [
        'available' => 'bg-success/15 border-success/40 text-success hover:bg-success/25',
        'reserved' => 'bg-warning/15 border-warning/40 text-warning',
        'occupied' => 'bg-base-300/60 border-base-300 text-base-content/50',
        'maintenance' => 'bg-error/10 border-error/30 text-error',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-base-content">Browse Available Stalls</h1>
            <p class="text-xs text-base-content/60 mt-1">Pick an available stall on any floor and apply online. No account needed.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-[11px] font-bold">
            <span class="badge badge-success badge-outline">Available</span>
            <span class="badge badge-warning badge-outline">Reserved</span>
            <span class="badge badge-ghost">Occupied</span>
            <span class="badge badge-error badge-outline">Maintenance</span>
        </div>
    </div>

    @if($locations->isEmpty())
        <div class="card bg-base-100 border border-base-300 rounded-3xl p-10 text-center text-sm text-base-content/60">
            No floors have been set up yet. Please check back soon.
        </div>
    @else
        <div role="tablist" class="tabs tabs-boxed bg-base-100 border border-base-300 rounded-2xl p-1 w-fit flex-wrap">
            @foreach($locations as $location)
                <button type="button" role="tab" wire:click="selectLocation({{ $location->id }})"
                    class="tab rounded-xl font-bold {{ $activeLocation && $activeLocation->id === $location->id ? 'tab-active' : '' }}">
                    {{ $location->name }}
                    <span class="ml-1.5 text-[10px] opacity-70">{{ $location->available_count }}/{{ $location->stalls_count }}</span>
                </button>
            @endforeach
        </div>

        @if($activeLocation)
            <div class="card bg-base-100 border border-base-300 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
                @if($activeLocation->description)
                    <p class="text-xs text-base-content/70">{{ $activeLocation->description }}</p>
                @endif

                @if($activeLocation->grid_rows && $activeLocation->grid_cols)
                    <div class="overflow-x-auto">
                        <div class="grid gap-2 min-w-[320px]" style="grid-template-columns: repeat({{ (int) $activeLocation->grid_cols }}, minmax(64px, 1fr));">
                            @for($row = 1; $row <= $activeLocation->grid_rows; $row++)
                                @for($col = 1; $col <= $activeLocation->grid_cols; $col++)
                                    @php $cell = $grid[$row][$col] ?? null; @endphp
                                    @if($cell)
                                        <button type="button" wire:click="selectStall({{ $cell->id }})"
                                            class="h-16 rounded-xl border text-xs font-black flex flex-col items-center justify-center transition {{ $statusStyles[$cell->status] ?? $statusStyles['occupied'] }}">
                                            <span>{{ $cell->stall_number }}</span>
                                            <span class="text-[9px] font-semibold uppercase tracking-wide opacity-80">{{ ucfirst($cell->status) }}</span>
                                        </button>
                                    @else
                                        <div class="h-16 rounded-xl border border-dashed border-base-300/70"></div>
                                    @endif
                                @endfor
                            @endfor
                        </div>
                    </div>
                @endif

                @if($otherStalls->isNotEmpty())
                    <div class="space-y-2">
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-base-content/60">Other stalls on this floor</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($otherStalls as $stall)
                                <button type="button" wire:click="selectStall({{ $stall->id }})"
                                    class="px-3 py-2 rounded-xl border text-xs font-black transition {{ $statusStyles[$stall->status] ?? $statusStyles['occupied'] }}">
                                    {{ $stall->stall_number }} &middot; {{ ucfirst($stall->status) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(empty($grid) && $otherStalls->isEmpty())
                    <p class="text-sm text-base-content/60 text-center py-6">No stalls on this floor yet.</p>
                @endif
            </div>
        @endif
    @endif

    @if($selectedStall)
        <div class="modal modal-open" role="dialog" aria-modal="true">
            <div class="modal-box rounded-3xl max-w-md">
                <button type="button" wire:click="closeStall" class="btn btn-sm btn-circle btn-ghost absolute right-3 top-3" aria-label="Close" x-data x-on:keydown.escape.window="isTopModal($el) && $wire.closeStall()">✕</button>
                @if($selectedStall->image)
                    <img src="{{ $selectedStall->image_url }}" alt="Stall {{ $selectedStall->stall_number }}" class="w-full h-44 object-cover rounded-2xl mb-4">
                @endif
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-primary">{{ $selectedStall->location->name ?? '' }}</span>
                <h3 class="text-xl font-black">Stall {{ $selectedStall->stall_number }}</h3>
                <p class="text-2xl font-black text-primary mt-2">₱{{ number_format($selectedStall->price, 2) }} <span class="text-xs font-semibold text-base-content/60">/ month</span></p>
                @if($selectedStall->description)
                    <p class="text-sm text-base-content/70 mt-3">{{ $selectedStall->description }}</p>
                @endif

                @if($submitted)
                    <div class="alert alert-success rounded-2xl mt-5 text-sm">
                        <span><strong>Application submitted.</strong> We emailed you a confirmation. The office will review it and get back to you.</span>
                    </div>
                @elseif($showApply && $selectedStall->status === 'available')
                    <form wire:submit="submitApplication" class="space-y-3 mt-5">
                        <div class="hidden" aria-hidden="true">
                            <label>Website <input type="text" wire:model="form.website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        @foreach([
                            'applicant_name' => ['Full name', 'text', 'name'],
                            'applicant_email' => ['Email', 'email', 'email'],
                            'applicant_phone' => ['Mobile number', 'tel', 'tel'],
                            'business_name' => ['Business name', 'text', 'organization'],
                            'business_type' => ['Business type (e.g. Retail, Food)', 'text', 'off'],
                        ] as $field => [$label, $type, $autocomplete])
                            <label class="form-control w-full">
                                <span class="label-text text-xs font-bold">{{ $label }}</span>
                                <input type="{{ $type }}" wire:model="form.{{ $field }}" autocomplete="{{ $autocomplete }}" class="input input-bordered input-sm rounded-xl w-full @error('form.' . $field) input-error @enderror">
                                @error('form.' . $field) <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                            </label>
                        @endforeach
                        <label class="form-control w-full">
                            <span class="label-text text-xs font-bold">Message (optional)</span>
                            <textarea wire:model="form.message" rows="2" class="textarea textarea-bordered rounded-xl text-sm w-full"></textarea>
                            @error('form.message') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </label>
                        <label class="form-control w-full">
                            <span class="label-text text-xs font-bold">Supporting document (optional, PDF/JPG/PNG, max 5 MB)</span>
                            <input type="file" wire:model="form.document" accept=".pdf,.jpg,.jpeg,.png" class="file-input file-input-bordered file-input-sm rounded-xl w-full">
                            <span wire:loading wire:target="form.document" class="text-xs text-base-content/60 mt-1">Uploading…</span>
                            @error('form.document') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                        </label>
                        <button type="submit" class="btn btn-primary rounded-xl font-bold w-full" wire:loading.attr="disabled" wire:target="submitApplication,form.document">
                            <span wire:loading.remove wire:target="submitApplication">Submit application</span>
                            <span wire:loading wire:target="submitApplication">Submitting…</span>
                        </button>
                    </form>
                @else
                <div class="modal-action">
                    @if($selectedStall->status === 'available')
                        <button type="button" wire:click="openApply" class="btn btn-primary rounded-xl font-bold w-full">Apply for this stall</button>
                    @else
                        <span class="badge badge-lg font-bold w-full py-4">{{ ucfirst($selectedStall->status) }} &mdash; not open for applications</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="modal-backdrop" wire:click="closeStall"></div>
        </div>
    @endif
</div>
