<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\User;
use App\Observers\ClientObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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

        // Atrás do proxy (Traefik) o TLS termina fora do container; força
        // a geração de URLs/assets em https para evitar mixed content.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Root pode tudo, sempre. Independente das permissões individuais.
        Gate::before(function (User $user, string $ability) {
            return $user->isRoot() ? true : null;
        });
    }
}
