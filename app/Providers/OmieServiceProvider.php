<?php

namespace App\Providers;

use App\Services\Omie\Contracts\ContasReceberRepositoryInterface;
use App\Services\Omie\Contracts\OmieClientInterface;
use App\Services\Omie\ContasReceberRepository;
use App\Services\Omie\OmieClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class OmieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OmieClientInterface::class, function (Application $app): OmieClient {
            return new OmieClient(
                http:      $app->make(HttpFactory::class),
                logger:    Log::channel('omie'),
                baseUrl:   (string) config('omie.api.url'),
                appKey:    (string) config('omie.api.key'),
                appSecret: (string) config('omie.api.secret'),
                timeout:   (int) config('omie.api.timeout', 30),
            );
        });

        $this->app->bind(ContasReceberRepositoryInterface::class, ContasReceberRepository::class);
    }
}
