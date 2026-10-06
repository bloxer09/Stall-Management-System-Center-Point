<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admins with 2FA enabled must pass the SMS challenge once per session, whichever way they signed in
 * (password modal, Google, or Facebook) — the guard sits on the routes, not the login form.
 */
class RequiresTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasTwoFactorEnabled() && !$request->session()->get('two_factor_verified')) {
            if ($request->expectsJson() || $request->hasHeader('X-Livewire')) {
                abort(403, 'Two-factor verification required.');
            }

            return redirect()->guest(route('two-factor.challenge'));
        }

        return $next($request);
    }
}
