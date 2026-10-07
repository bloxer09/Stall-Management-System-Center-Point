<?php

namespace App\Providers;

use App\Http\Middleware\RequiresTwoFactor;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Livewire actions on admin pages go to /livewire/update; re-apply the 2FA gate there too.
        Livewire::addPersistentMiddleware([
            RequiresTwoFactor::class,
            \App\Http\Middleware\EnsureAccountActive::class,
        ]);

        // Every login (password, Google, Facebook, set-password, remember-me) starts unverified,
        // so a second user signing in on the same session never inherits the first user's 2FA pass.
        Event::listen(Login::class, function () {
            session()->forget('two_factor_verified');
        });
    }
}
