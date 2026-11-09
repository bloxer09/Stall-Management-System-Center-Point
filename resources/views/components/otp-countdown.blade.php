@props(['expiresAt', 'context'])

@php
    $remaining = max(0, (int) $expiresAt - now()->timestamp);
@endphp

<div wire:key="otp-countdown-{{ $context }}-{{ $expiresAt ?? 'expired' }}"
    data-otp-countdown data-expires-at="{{ $expiresAt }}" data-server-now="{{ now()->timestamp }}"
    class="text-sm text-base-content">
    <p data-otp-active @if($remaining === 0) hidden @endif>
        Code expires in <span role="timer" aria-label="Time until code expires" aria-live="off"
            class="font-mono font-semibold tabular-nums">{{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}</span>
    </p>
    <p role="status" class="font-semibold">{{ $remaining === 0 ? 'Code expired. Request a new code to continue.' : '' }}</p>
</div>
