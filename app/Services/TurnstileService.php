<?php

namespace App\Services;

/**
 * Cloudflare Turnstile CAPTCHA. Switched on only when both keys are configured, so local
 * development and the offline demo never need it.
 */
class TurnstileService
{
    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }
}
