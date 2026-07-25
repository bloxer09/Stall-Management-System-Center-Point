<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequiresTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        // Allow disabling 2FA via .env or config for testing purposes
        $enable2fa = config('services.enable_2fa', env('ENABLE_2FA', true));
        if (!filter_var($enable2fa, FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user && $user->two_factor_enabled && ($user->isAdmin() || $user->isStaff())) {
            if (!$request->session()->has('2fa_verified')) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
