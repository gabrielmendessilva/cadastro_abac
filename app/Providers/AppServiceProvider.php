<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\User;
use App\Observers\ClientObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();
        Client::observe(ClientObserver::class);

        // Root pode tudo, sempre. Independente das permissões individuais.
        Gate::before(function (User $user, string $ability) {
            return $user->isRoot() ? true : null;
        });
    }
}
