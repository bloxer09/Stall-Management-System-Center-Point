<?php

namespace App\Providers;

use App\Http\Middleware\RequiresTwoFactor;
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
        ]);
    }
}
