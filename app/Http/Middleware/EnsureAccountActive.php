<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose account an admin deactivated, on their very next request
 * (page load or Livewire action), even if they signed in before it happened.
 */
class EnsureAccountActive
{
    public const MESSAGE = 'This account is deactivated. Please contact the office.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDeactivated()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('error', self::MESSAGE);
        }

        return $next($request);
    }
}
