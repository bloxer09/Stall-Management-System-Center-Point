@props(['model', 'id', 'name' => null])
<div class="relative" x-data="{
    number: $wire.entangle(@js($model)),
    get national() {
        const value = String(this.number ?? '');
        return /^\+?\d+$/.test(value) ? value.replace(/^(?:\+63|63|0)/, '') : value;
    },
    set national(value) {
        const trimmed = value.trim();
        this.number = /^\+?\d+$/.test(trimmed)
            ? '0' + trimmed.replace(/^(?:\+63|63|0)/, '')
            : value;
    }
}">
    <span id="{{ $id }}-country" class="absolute left-3 top-0 h-full flex items-center text-sm font-semibold text-base-content pointer-events-none">+63</span>
    <input id="{{ $id }}" type="tel" x-model="national" inputmode="tel" autocomplete="tel-national"
        pattern="9[0-9]{9}" title="Enter 10 digits starting with 9."
        aria-describedby="{{ $id }}-country {{ $id }}-hint{{ $errors->has($name ?? $model) ? ' ' . $id . '-error' : '' }}"
        aria-invalid="{{ $errors->has($name ?? $model) ? 'true' : 'false' }}"
        {{ $attributes->class(['input input-bordered w-full rounded-xl text-sm pl-14'])->merge(['placeholder' => '9171234567']) }} />
</div>
<p id="{{ $id }}-hint" class="text-xs text-base-content mt-1">Enter 10 digits starting with 9.</p>
