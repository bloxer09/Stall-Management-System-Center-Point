<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare Turnstile CAPTCHA. Switched on only when both keys are configured, so local
 * development and the offline demo never need it.
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public function siteKey(): string
    {
        return (string) config('services.turnstile.site_key');
    }

    /**
     * Ask Cloudflare whether the widget token is genuine. Any failure (no token, rejected,
     * network error, timeout) counts as "not verified".
     */
    public function verify(?string $token, string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(2)->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return $response->successful() && $response->json('success') === true;
        } catch (Throwable) {
            return false;
        }
    }
}
