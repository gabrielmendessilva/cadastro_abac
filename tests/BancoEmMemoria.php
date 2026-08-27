<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

/**
 * Schema de teste em sqlite na memoria - sem nenhum comando que derrube tabelas.
 *
 * Existe no lugar do RefreshDatabase do Laravel. Aquele trait chama `migrate:fresh`,
 * que dropa o schema inteiro da conexao em que estiver apontado, e ele aponta para
 * onde o config mandar. Aqui so existe `migrate`, e so depois que
 * TestCase::exigirSqliteEmMemoria() provou que a conexao e o sqlite descartavel -
 * que nasce vazio a cada teste e morre no fim dele.
 */
trait BancoEmMemoria
{
    protected function setUpBancoEmMemoria(): void
    {
        $this->exigirSqliteEmMemoria();

        $this->artisan('migrate');

        $this->app[Kernel::class]->setArtisan(null);
    }
}
