@props(['name', 'label', 'type' => 'text', 'id' => null, 'revealPassword' => false])
@php $fieldId = $id ?? 'field-' . $name; @endphp
<div class="form-control" wire:key="input-{{ $fieldId }}">
    <label for="{{ $fieldId }}" class="label pb-1">
        <span class="label-text font-semibold text-xs text-base-content/80">{{ $label }}</span>
    </label>
    @if($type === 'tel')
        <x-phone-input :id="$fieldId" :name="$name" :model="$attributes->get('wire:model')" {{ $attributes->except(['wire:model', 'autocomplete', 'placeholder']) }} />
    @else
    <div class="relative" @if($revealPassword && $type === 'password') x-data="{ passwordVisible: false }" x-on:auth-closed.window="passwordVisible = false" @endif>
        <input id="{{ $fieldId }}" type="{{ $type }}"
            @if($revealPassword && $type === 'password') :type="passwordVisible ? 'text' : 'password'" @endif
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            @if($errors->has($name)) aria-describedby="{{ $fieldId }}-error" @endif
            {{ $attributes->class(['input input-bordered w-full rounded-xl text-sm', 'pr-14' => $revealPassword && $type === 'password']) }} />
        @if($revealPassword && $type === 'password')
            <button type="button" class="absolute right-2 top-0 h-full w-11 flex items-center justify-center rounded-xl text-base-content"
                @click="passwordVisible = !passwordVisible"
                :aria-label="passwordVisible ? 'Hide {{ $label }}' : 'Show {{ $label }}'"
                :aria-pressed="passwordVisible" aria-controls="{{ $fieldId }}">
                <svg x-show="!passwordVisible" aria-hidden="true" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg x-show="passwordVisible" x-cloak aria-hidden="true" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="m3 3 18 18M10.5 5.1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.2 4.1M6.1 6.1A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.9-1.9M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
                </svg>
            </button>
        @endif
    </div>
    @endif
    @error($name)
        <span id="{{ $fieldId }}-error" role="alert" class="text-xs text-error mt-1">{{ $message }}</span>
    @enderror
</div>
