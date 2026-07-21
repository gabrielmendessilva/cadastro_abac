<?php

namespace App\Services\Rm;

use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\Exceptions\RmImportException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Psr\Log\LoggerInterface;

/**
 * Implementação da leitura do TOTVS RM via conexão sqlsrv (config/database.php: 'rm').
 * Somente SELECTs — nada é escrito na origem.
 */
class RmSqlServerReader implements RmReaderInterface
{
    /**
     * Colunas lidas da FCFO (a tabela tem 194 — buscar só o necessário).
     * @var list<string>
     */
    private const FCFO_COLUMNS = [
        'CODCOLIGADA', 'CODCFO', 'CGCCFO', 'NOME', 'NOMEFANTASIA', 'PAGREC', 'ATIVO',
        'PESSOAFISOUJUR', 'EMAIL', 'EMAILFISCAL', 'EMAILPGTO', 'EMAILENTREGA',
        'TELEFONE', 'TELEX', 'CONTATO', 'INSCRESTADUAL', 'INSCRMUNICIPAL',
        'CIDENTIDADE', 'DTNASCIMENTO', 'DTINICATIVIDADES', 'RAMOATIV', 'CAMPOLIVRE',
        'RUA', 'NUMERO', 'COMPLEMENTO', 'BAIRRO', 'CIDADE', 'CODETD', 'CEP', 'PAIS', 'CODMUNICIPIO',
        'RUAPGTO', 'NUMEROPGTO', 'COMPLEMENTOPGTO', 'BAIRROPGTO', 'CIDADEPGTO',
        'CODETDPGTO', 'CEPPGTO', 'PAISPAGTO', 'CODMUNICIPIOPGTO',
        'RUAENTREGA', 'NUMEROENTREGA', 'COMPLEMENTREGA', 'BAIRROENTREGA', 'CIDADEENTREGA',
        'CODETDENTREGA', 'CEPENTREGA', 'PAISENTREGA', 'CODMUNICIPIOENTREGA',
    ];

    /** @var list<string> */
    private const FCFOCONTATO_COLUMNS = [
        'CODCOLIGADA', 'CODCFO', 'IDCONTATO', 'NOME', 'EMAIL', 'TELEFONE',
        'RAMAL', 'FAX', 'FUNCAO', 'ATIVO', 'DATANASCIMENTO', 'OBSERVACAO',
    ];

    /** @var list<string> */
    private const GCCUSTO_COLUMNS = [
        'CODCOLIGADA', 'CODCCUSTO', 'NOME', 'CODREDUZIDO', 'CODCLASSIFICA',
        'ATIVO', 'PERMITELANC', 'RESPONSAVEL',
    ];

    /** Colunas padrão de FCFOCONTATOCOMPL — o que sobrar é campo complementar custom. */
    private const CONTATO_COMPL_STANDARD = [
        'CODCOLIGADA', 'CODCFO', 'IDCONTATO',
        'RECCREATEDBY', 'RECCREATEDON', 'RECMODIFIEDBY', 'RECMODIFIEDON',
    ];

    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly LoggerInterface $logger,
    ) {}

    public function preflight(): void
    {
        $required = [
            'FCFO' => self::FCFO_COLUMNS,
            'FCFOCONTATO' => self::FCFOCONTATO_COLUMNS,
            'FCFODEF' => ['CODCOLIGADA', 'CODCOLCFO', 'CODCFO', 'CODCCUSTO'],
            'GCCUSTO' => self::GCCUSTO_COLUMNS,
        ];

        $found = $this->columnsByTable(array_keys($required));

        foreach ($required as $table => $columns) {
            if (! isset($found[$table])) {
                throw RmImportException::tabelaAusente($table);
            }

            $missing = array_values(array_diff($columns, $found[$table]));
            if ($missing !== []) {
                throw RmImportException::colunasAusentes($table, $missing);
            }
        }

        $this->logger->info('rm.preflight.ok', ['tabelas' => array_keys($required)]);
    }

    public function countFcfo(?int $coligada = null): int
    {
        return $this->fcfoQuery($coligada)->count();
    }

    public function eachFcfoChunk(int $chunkSize, ?int $coligada, ?int $limit, callable $handle): void
    {
        $remaining = $limit;

        $this->fcfoQuery($coligada)
            ->select(self::FCFO_COLUMNS)
            ->orderBy('CODCOLIGADA')
            ->orderBy('CODCFO')
            ->chunk($chunkSize, function ($rows) use (&$remaining, $handle): ?bool {
                $rows = array_map(static fn (object $row): array => (array) $row, $rows->all());

                if ($remaining !== null) {
                    $rows = array_slice($rows, 0, max(0, $remaining));
                    $remaining -= count($rows);
                }

                if ($rows !== []) {
                    $handle($rows);
                }

                return ($remaining !== null && $remaining <= 0) ? false : null;
            });
    }

    public function contatosForKeys(array $codesByColigada): array
    {
        if ($codesByColigada === []) {
            return [];
        }

        $rows = $this->connection->table('FCFOCONTATO')
            ->select(self::FCFOCONTATO_COLUMNS)
            ->where(function (Builder $query) use ($codesByColigada): void {
                foreach ($codesByColigada as $coligada => $codes) {
                    $query->orWhere(function (Builder $inner) use ($coligada, $codes): void {
                        $inner->where('CODCOLIGADA', $coligada)->whereIn('CODCFO', $codes);
                    });
                }
            })
            ->orderBy('CODCOLIGADA')->orderBy('CODCFO')->orderBy('IDCONTATO')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $grouped[$row['CODCOLIGADA'] . '|' . trim((string) $row['CODCFO'])][] = $row;
        }

        return $grouped;
    }

    public function defaultsForKeys(array $codesByColigada): array
    {
        if ($codesByColigada === []) {
            return [];
        }

        $rows = $this->connection->table('FCFODEF')
            ->select(['CODCOLIGADA', 'CODCOLCFO', 'CODCFO', 'CODCCUSTO'])
            ->whereNotNull('CODCCUSTO')
            ->where(function (Builder $query) use ($codesByColigada): void {
                foreach ($codesByColigada as $coligada => $codes) {
                    $query->orWhere(function (Builder $inner) use ($coligada, $codes): void {
                        $inner->where('CODCOLCFO', $coligada)->whereIn('CODCFO', $codes);
                    });
                }
            })
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $grouped[$row['CODCOLCFO'] . '|' . trim((string) $row['CODCFO'])][] = $row;
        }

        return $grouped;
    }

    public function allCentrosCusto(): array
    {
        return $this->connection->table('GCCUSTO')
            ->select(self::GCCUSTO_COLUMNS)
            ->orderBy('CODCOLIGADA')->orderBy('CODCCUSTO')
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();
    }

    public function contatoComplCustomColumns(): array
    {
        $found = $this->columnsByTable(['FCFOCONTATOCOMPL']);

        if (! isset($found['FCFOCONTATOCOMPL'])) {
            return []; // tabela é opcional — nem toda instalação a possui
        }

        return array_values(array_diff($found['FCFOCONTATOCOMPL'], self::CONTATO_COMPL_STANDARD));
    }

    public function contatosComplForKeys(array $codesByColigada, array $columns): array
    {
        if ($codesByColigada === [] || $columns === []) {
            return [];
        }

        $rows = $this->connection->table('FCFOCONTATOCOMPL')
            ->select(array_merge(['CODCOLIGADA', 'CODCFO', 'IDCONTATO'], $columns))
            ->where(function (Builder $query) use ($codesByColigada): void {
                foreach ($codesByColigada as $coligada => $codes) {
                    $query->orWhere(function (Builder $inner) use ($coligada, $codes): void {
                        $inner->where('CODCOLIGADA', $coligada)->whereIn('CODCFO', $codes);
                    });
                }
            })
            ->get();

        $keyed = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $key = $row['CODCOLIGADA'] . '|' . trim((string) $row['CODCFO']) . '|' . $row['IDCONTATO'];
            $keyed[$key] = $row;
        }

        return $keyed;
    }

    private function fcfoQuery(?int $coligada): Builder
    {
        $query = $this->connection->table('FCFO');

        if ($coligada !== null) {
            $query->where('CODCOLIGADA', $coligada);
        }

        return $query;
    }

    /**
     * @param  list<string>  $tables
     * @return array<string,list<string>> tabela => colunas encontradas
     */
    private function columnsByTable(array $tables): array
    {
        $placeholders = implode(', ', array_fill(0, count($tables), '?'));

        $rows = $this->connection->select(
            'SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ' .
            "WHERE TABLE_NAME IN ({$placeholders}) ORDER BY TABLE_NAME, ORDINAL_POSITION",
            $tables
        );

        $found = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $found[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
        }

        return $found;
    }
}
