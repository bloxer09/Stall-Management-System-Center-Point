<?php

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-side protections sent with every response: no framing by other sites (clickjacking),
 * no MIME sniffing, a tight referrer policy, no camera/mic/location/payment APIs, HSTS once the
 * site is served over HTTPS, and a Content-Security-Policy that only runs scripts carrying this
 * session's nonce.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->nonce($request);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $mode = config('security.csp_mode', 'enforce');
        if ($mode === 'enforce' || $mode === 'report') {
            $headers->set($mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $this->policy($nonce));
        }

        return $response;
    }

    /**
     * One nonce per session, not per request: wire:navigate swaps pages without a reload, and the
     * browser keeps enforcing the first page's policy, so the scripts of every later page must
     * carry the same nonce. Another visitor still cannot know it.
     */
    private function nonce(Request $request): string
    {
        if (!$request->hasSession()) {
            return Str::random(32);
        }

        $nonce = $request->session()->get('csp_nonce');
        if (!is_string($nonce) || $nonce === '') {
            $nonce = Str::random(32);
            $request->session()->put('csp_nonce', $nonce);
        }

        return $nonce;
    }

    private function policy(string $nonce): string
    {
        $turnstile = app(TurnstileService::class)->enabled() ? ' https://challenges.cloudflare.com' : '';

        // 'unsafe-eval' stays until a Livewire 4 upgrade: Alpine evaluates x-data / wire: expressions at runtime.
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$turnstile}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-src 'self'{$turnstile}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
