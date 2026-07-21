<?php

namespace App\Providers;

use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\RmImportService;
use App\Services\Rm\RmSqlServerReader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class RmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // DB::connection é resolvido DENTRO da closure (lazy): o boot da app nunca
        // tenta abrir conexão com o SQL Server — só quem usar o reader.
        $this->app->singleton(RmReaderInterface::class, function (Application $app): RmSqlServerReader {
            return new RmSqlServerReader(
                connection: DB::connection((string) config('rm.connection', 'rm')),
                logger: Log::channel('rm'),
                schema: (string) config('rm.schema', 'dbo'),
            );
        });

        $this->app->singleton(RmImportService::class, function (Application $app): RmImportService {
            return new RmImportService(
                reader: $app->make(RmReaderInterface::class),
                logger: Log::channel('rm'),
            );
        });
    }
}
