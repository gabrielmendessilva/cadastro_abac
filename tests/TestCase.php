<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Rede de segurança para as classes que não usam o BancoEmMemoria: elas
        // montam schema na mão, e na mão ou no trait o destino tem de ser o mesmo
        // sqlite descartável.
        $this->exigirSqliteEmMemoria();
    }

    /**
     * Portão de entrada da suíte: sem sqlite em memória, nenhum teste roda.
     *
     * Os `<env>` do phpunit.xml apontam para sqlite `:memory:`, mas eles perdem para
     * um `bootstrap/cache/config.php` gerado a partir do .env de produção (o
     * entrypoint da imagem roda `config:cache` no boot). Quando isso acontece, a
     * conexão "de teste" é a de produção — foi assim que a base foi apagada em
     * 27/08/2026. Sem tratamento de erro nem fallback aqui: a suíte tem de morrer
     * barulhenta, e não seguir contra um banco real.
     */
    protected function exigirSqliteEmMemoria(): void
    {
        $conexao = DB::connection();
        $driver = $conexao->getDriverName();
        $banco = (string) $conexao->getDatabaseName();

        if ($driver === 'sqlite' && in_array($banco, [':memory:', ''], true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            "SUITE ABORTADA: a conexao padrao dos testes e '%s' no banco '%s', e nao sqlite em memoria.\n"
            ."Rodar os testes assim escreveria (e apagaria) dados desse banco.\n"
            .'Causa provavel: bootstrap/cache/config.php gerado com o .env de producao sobrepondo o phpunit.xml - apague-o e rode de novo.',
            $driver,
            $banco
        ));
    }

    protected function tearDown(): void
    {
        // O Eloquent cacheia por classe, em estático, as colunas que podem entrar
        // no mass assignment (GuardsAttributes::$guardableColumns). Testes de
        // importação que criam um schema mínimo na mão (Associados/Rm) poluiriam
        // esse cache para as classes de teste seguintes — campos passariam a ser
        // descartados em silêncio mesmo com o schema completo migrado.
        $guardable = new ReflectionProperty(Model::class, 'guardableColumns');
        $guardable->setValue(null, []);

        parent::tearDown();
    }
}
