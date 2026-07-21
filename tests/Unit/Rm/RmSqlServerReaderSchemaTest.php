<?php

namespace Tests\Unit\Rm;

use App\Services\Rm\RmSqlServerReader;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Instalações do RM com auditoria ligada mantêm um schema espelho (TOTVSAUDIT)
 * com uma cópia de cada tabela, acrescida das colunas de log. A introspecção de
 * FCFOCONTATOCOMPL não filtrava por schema e fundia as duas — o SELECT saía com
 * colunas duplicadas e com campos que só existem na cópia de auditoria, e o
 * import morria com "Invalid column name 'LOGUSER'".
 */
class RmSqlServerReaderSchemaTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_introspeccao_filtra_pelo_schema_de_dados(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $connection->shouldReceive('select')
            ->once()
            ->with(
                Mockery::on(fn (string $sql): bool => str_contains($sql, 'TABLE_SCHEMA = ?')),
                Mockery::on(fn (array $bindings): bool => $bindings[0] === 'dbo'),
            )
            ->andReturn([
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'CODCOLIGADA'],
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'CODCFO'],
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'IDCONTATO'],
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'DEPTO'],
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'COMITE'],
                (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'RECCREATEDBY'],
            ]);

        $reader = new RmSqlServerReader($connection, new NullLogger, 'dbo');

        // Só os campos custom sobram; os padrão do RM são descartados.
        $this->assertSame(['DEPTO', 'COMITE'], $reader->contatoComplCustomColumns());
    }

    public function test_colunas_repetidas_nao_saem_duplicadas(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        // Simula o que acontecia quando dois schemas eram fundidos.
        $connection->shouldReceive('select')->once()->andReturn([
            (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'DEPTO'],
            (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'ANIV'],
            (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'DEPTO'],
            (object) ['TABLE_NAME' => 'FCFOCONTATOCOMPL', 'COLUMN_NAME' => 'ANIV'],
        ]);

        $reader = new RmSqlServerReader($connection, new NullLogger, 'dbo');

        $this->assertSame(['DEPTO', 'ANIV'], $reader->contatoComplCustomColumns());
    }

    public function test_schema_e_configuravel(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);

        $connection->shouldReceive('select')
            ->once()
            ->with(Mockery::any(), Mockery::on(fn (array $b): bool => $b[0] === 'OUTRO'))
            ->andReturn([]);

        $reader = new RmSqlServerReader($connection, new NullLogger, 'OUTRO');

        $this->assertSame([], $reader->contatoComplCustomColumns());
    }
}
