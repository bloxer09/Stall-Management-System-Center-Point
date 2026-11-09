{{-- Cloudflare Turnstile widget; renders nothing unless keys are configured. Use inside a Livewire form. --}}
@php($turnstile = app(\App\Services\TurnstileService::class))
@if($turnstile->enabled())
    <div wire:ignore
         x-data
         x-init="
            const render = () => window.turnstile.render($refs.box, {
                sitekey: @js($turnstile->siteKey()),
                callback: (token) => $wire.set('captchaToken', token, false),
                'expired-callback': () => $wire.set('captchaToken', '', false),
            });
            window.turnstile ? render() : window.addEventListener('turnstile-ready', render, { once: true });
         "
         x-on:turnstile-reset.window="window.turnstile && window.turnstile.reset($refs.box)">
        <div x-ref="box" class="cf-turnstile" data-sitekey="{{ $turnstile->siteKey() }}"></div>
    </div>
    @once
        <script nonce="{{ Vite::cspNonce() }}">
            window.onTurnstileLoad = () => window.dispatchEvent(new Event('turnstile-ready'));
        </script>
        <script nonce="{{ Vite::cspNonce() }}" src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad" async defer></script>
    @endonce
@endif
