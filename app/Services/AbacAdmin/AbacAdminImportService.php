<?php

namespace App\Services\AbacAdmin;

use App\Services\AbacAdmin\Exceptions\AbacAdminImportException;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Migração do banco legado abac_admin (conexão 'abac_admin') para o banco default do app:
 * primeiro clients, depois client_contatos.
 *
 * Schemas verificados nos bancos vivos (2026-07-21):
 * - Origem clients é PT-BR (nome, cpf_cnpj, email_admin...); destino é o schema das
 *   migrations (name, document UNIQUE, email, phone...). O de-para está em
 *   CLIENT_RENAMES; campos da origem sem coluna no destino são preservados em
 *   `notes` com rótulo (CLIENT_TO_NOTES + email_2..7) em vez de descartados.
 * - client_contatos tem os mesmos nomes PT-BR dos dois lados (interseção direta).
 *
 * Regras:
 * - Ids NÃO são preservados: o destino gera novos e os client_id dos contatos são
 *   remapeados. user_id não é copiado (aponta para users do banco antigo).
 * - Dedup de cliente por documento normalizado (dígitos); sem documento, por nome.
 *   Quem já existe é pulado, mas os contatos são mesclados.
 * - Contato com client_id inexistente na origem é órfão: pulado e reportado.
 *   E-mail já existente para aquele cliente é pulado; sem e-mail, dedup por nome.
 * - Valores são truncados no limite de cada coluna do destino (strict mode ligado).
 * - Idempotente: re-executar não duplica nada.
 */
class AbacAdminImportService
{
    /** De-para de colunas clients: origem (PT-BR legado) => destino (schema das migrations). */
    private const CLIENT_RENAMES = [
        'nome' => 'name',
        'nome_fantasia' => 'fantasy_name',
        'cpf_cnpj' => 'document',
        'email_admin' => 'email',
        'telefone' => 'phone',
        'celular_admin' => 'mobile',
        'segmentos' => 'segmento',
    ];

    /** Colunas da origem sem coluna no destino: viram linhas rotuladas em notes. */
    private const CLIENT_TO_NOTES = [
        'obs_2' => 'Obs 2',
        'classificacao' => 'Classificação',
        'categoria' => 'Categoria',
        'inscri_estadual' => 'Inscrição Estadual',
        'inscri_municipal' => 'Inscrição Municipal',
        'tipo_cliente' => 'Tipo de cliente',
        'contato_name_admin' => 'Contato (admin)',
        'regional' => 'Regional',
        'associado' => 'Associado',
        'situacao_abac' => 'Situação ABAC',
        'dt_bacen' => 'Data Bacen',
        'classificao_administradora' => 'Classificação administradora',
        'email_conac' => 'E-mail CONAC',
        'area_atuacao' => 'Área de atuação',
    ];

    /** Referências a tabelas do modelo antigo — não copiáveis. */
    private const CLIENT_EXCLUDES = ['id', 'status', 'endereco_id', 'opcional_id'];

    private const EXTRA_EMAILS = ['email_2', 'email_3', 'email_4', 'email_5', 'email_6', 'email_7'];

    /** @var array<string,int> destino: dígitos do documento => clients.id */
    private array $byDigits = [];

    /** @var array<string,int> destino: nome normalizado => clients.id (linhas sem documento) */
    private array $byNomeSemDoc = [];

    /** @var array<int,list<string>> destino: clients.id => e-mails do cadastro */
    private array $emailSeed = [];

    /** @var array<int|string,array{emails:array<string,true>,names:array<string,true>}> */
    private array $contactKeys = [];

    /** @var array<int,int> origem clients.id => destino clients.id (negativo no dry-run) */
    private array $idMap = [];

    /** @var array<int,true> ids de clients existentes na origem (detecção de órfão) */
    private array $sourceClientIds = [];

    /** @var array<string,true> documentos já vistos na origem nesta execução */
    private array $srcSeenDigits = [];

    /** @var array<string,true> nomes (sem doc) já vistos na origem nesta execução */
    private array $srcSeenNomes = [];

    /** @var array<string,string> plano de cópia de clients: coluna origem => coluna destino */
    private array $clientCopyPlan = [];

    /** @var list<string> colunas da origem que vão para notes */
    private array $clientNotesPlan = [];

    /** @var list<string> colunas de contatos copiadas (mesmo nome nos dois lados) */
    private array $contatoCopyPlan = [];

    /** @var array<string,array<string,int>> limites de varchar do destino por tabela */
    private array $limits = ['clients' => [], 'client_contatos' => []];

    private bool $sourceHasStatus = false;

    private int $fakeId = 0;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $sourceConnection = 'abac_admin',
    ) {}

    public function run(AbacAdminImportOptions $options): AbacAdminImportReport
    {
        $report = new AbacAdminImportReport($options->maxWarningSamples);
        $this->resetState();

        $this->logger->info('abac_admin.import.start', [
            'dry_run' => $options->dryRun,
            'limit' => $options->limit,
            'chunk' => $options->chunkSize,
        ]);

        $this->preflight($options, $report);

        $this->loadDestinationMaps($report);
        $this->loadSourceClientIds($options);

        $this->migrateClients($options, $report);
        $this->migrateContatos($options, $report);

        $this->logger->info('abac_admin.import.done', $report->toArray());

        return $report;
    }

    public function countSource(AbacAdminImportOptions $options): int
    {
        $clients = $this->source()->table($options->sourceClientsTable)->count();
        $contatos = $this->source()->table($options->sourceContatosTable)->count();

        return ($options->limit !== null ? min($options->limit, $clients) : $clients) + $contatos;
    }

    private function resetState(): void
    {
        $this->byDigits = [];
        $this->byNomeSemDoc = [];
        $this->emailSeed = [];
        $this->contactKeys = [];
        $this->idMap = [];
        $this->sourceClientIds = [];
        $this->srcSeenDigits = [];
        $this->srcSeenNomes = [];
        $this->clientCopyPlan = [];
        $this->clientNotesPlan = [];
        $this->contatoCopyPlan = [];
        $this->limits = ['clients' => [], 'client_contatos' => []];
        $this->sourceHasStatus = false;
        $this->fakeId = 0;
    }

    private function source(): \Illuminate\Database\ConnectionInterface
    {
        return DB::connection($this->sourceConnection);
    }

    /**
     * Valida tabelas/colunas essenciais e monta o plano de cópia (de-para + notes + descartes).
     */
    private function preflight(AbacAdminImportOptions $options, AbacAdminImportReport $report): void
    {
        $srcSchema = Schema::connection($this->sourceConnection);

        foreach ([$options->sourceClientsTable, $options->sourceContatosTable] as $table) {
            if (! $srcSchema->hasTable($table)) {
                throw AbacAdminImportException::tabelaAusente($this->sourceConnection, $table);
            }
        }

        foreach (['clients', 'client_contatos'] as $table) {
            if (! Schema::hasTable($table)) {
                throw AbacAdminImportException::tabelaAusente('default', $table);
            }
        }

        $srcClientCols = $srcSchema->getColumnListing($options->sourceClientsTable);
        $dstClientCols = Schema::getColumnListing('clients');

        foreach (['cpf_cnpj', 'nome'] as $col) {
            if (! in_array($col, $srcClientCols, true)) {
                throw AbacAdminImportException::colunaObrigatoriaAusente($this->sourceConnection, $options->sourceClientsTable, $col);
            }
        }
        foreach (['document', 'name'] as $col) {
            if (! in_array($col, $dstClientCols, true)) {
                throw AbacAdminImportException::colunaObrigatoriaAusente('default', 'clients', $col);
            }
        }

        $descartadas = [];

        foreach ($srcClientCols as $col) {
            if (in_array($col, self::CLIENT_EXCLUDES, true)) {
                if (in_array($col, ['endereco_id', 'opcional_id'], true)) {
                    $descartadas[] = $options->sourceClientsTable . '.' . $col;
                }
                if ($col === 'status') {
                    $this->sourceHasStatus = true; // convertido à parte (varchar -> tinyint)
                }

                continue;
            }

            $renamed = self::CLIENT_RENAMES[$col] ?? null;

            if ($renamed !== null && in_array($renamed, $dstClientCols, true)) {
                $this->clientCopyPlan[$col] = $renamed;
            } elseif ($col === 'obs' || isset(self::CLIENT_TO_NOTES[$col]) || in_array($col, self::EXTRA_EMAILS, true)) {
                $this->clientNotesPlan[] = $col;
            } elseif (in_array($col, $dstClientCols, true)) {
                $this->clientCopyPlan[$col] = $col;
            } else {
                $descartadas[] = $options->sourceClientsTable . '.' . $col;
            }
        }

        $srcContatoCols = $srcSchema->getColumnListing($options->sourceContatosTable);
        if (! in_array('client_id', $srcContatoCols, true)) {
            throw AbacAdminImportException::colunaObrigatoriaAusente($this->sourceConnection, $options->sourceContatosTable, 'client_id');
        }

        $dstContatoCols = Schema::getColumnListing('client_contatos');
        $this->contatoCopyPlan = array_values(array_diff(array_intersect($srcContatoCols, $dstContatoCols), ['id', 'client_id', 'user_id']));

        foreach (array_diff($srcContatoCols, $dstContatoCols, ['id', 'user_id']) as $col) {
            $descartadas[] = $options->sourceContatosTable . '.' . $col;
        }

        $this->limits['clients'] = $this->columnLimits('clients');
        $this->limits['client_contatos'] = $this->columnLimits('client_contatos');

        if ($descartadas !== []) {
            $report->colunasDescartadas = $descartadas;
            $this->warn($report, 'Colunas da origem sem coluna correspondente no destino — valores descartados', [
                'colunas' => $descartadas,
            ]);
        }
    }

    /**
     * Limites de varchar/char do destino (strict mode: valor maior que a coluna estoura).
     *
     * @return array<string,int>
     */
    private function columnLimits(string $table): array
    {
        $limits = [];

        foreach (Schema::getColumns($table) as $col) {
            if (preg_match('/^(?:var)?char\((\d+)\)/i', (string) ($col['type'] ?? '')) === 1) {
                preg_match('/\((\d+)\)/', (string) $col['type'], $m);
                $limits[$col['name']] = (int) $m[1];
            }
        }

        return $limits;
    }

    private function truncate(string $table, string $column, mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $max = $this->limits[$table][$column] ?? null;

        return $max !== null ? mb_substr($value, 0, $max) : $value;
    }

    private function loadDestinationMaps(AbacAdminImportReport $report): void
    {
        $emailCols = array_values(array_intersect(
            ['email', 'email_admin', 'email_2', 'email_3', 'email_4', 'email_5', 'email_6', 'email_7'],
            Schema::getColumnListing('clients'),
        ));

        $rows = DB::table('clients')
            ->orderBy('id')
            ->get(array_merge(['id', 'document', 'name'], $emailCols));

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $digits = Normalizer::digits((string) $row->document);

            if ($digits !== '') {
                if (isset($this->byDigits[$digits])) {
                    $this->warn($report, 'Documento duplicado já existente no destino — usando o menor id', [
                        'documento' => $digits,
                        'id_usado' => $this->byDigits[$digits],
                        'id_ignorado' => $id,
                    ]);
                } else {
                    $this->byDigits[$digits] = $id;
                }
            } else {
                $nomeKey = Normalizer::normalizeName((string) ($row->name ?? ''));
                if ($nomeKey !== '' && ! isset($this->byNomeSemDoc[$nomeKey])) {
                    $this->byNomeSemDoc[$nomeKey] = $id;
                }
            }

            $emails = [];
            foreach ($emailCols as $col) {
                $email = mb_strtolower(trim((string) $row->{$col}));
                if ($email !== '') {
                    $emails[] = $email;
                }
            }
            if ($emails !== []) {
                $this->emailSeed[$id] = $emails;
            }
        }
    }

    private function loadSourceClientIds(AbacAdminImportOptions $options): void
    {
        foreach ($this->source()->table($options->sourceClientsTable)->pluck('id') as $id) {
            $this->sourceClientIds[(int) $id] = true;
        }
    }

    private function migrateClients(AbacAdminImportOptions $options, AbacAdminImportReport $report): void
    {
        $remaining = $options->limit;

        $this->source()->table($options->sourceClientsTable)
            ->orderBy('id')
            ->chunk($options->chunkSize, function ($rows) use (&$remaining, $options, $report): ?bool {
                $rows = $rows->map(static fn (object $row): array => (array) $row)->all();

                if ($remaining !== null) {
                    $rows = array_slice($rows, 0, max(0, $remaining));
                    $remaining -= count($rows);
                }

                foreach ($rows as $row) {
                    try {
                        $this->processClientRow($row, $options, $report);
                    } catch (Throwable $e) {
                        $report->erros++;
                        $this->warn($report, 'Falha ao migrar cliente — linha pulada', [
                            'origem_id' => $row['id'] ?? null,
                            'erro' => $e->getMessage(),
                        ]);
                    }
                }

                if ($rows !== [] && $options->onChunk !== null) {
                    ($options->onChunk)(count($rows));
                }

                return ($remaining !== null && $remaining <= 0) ? false : null;
            });
    }

    /**
     * @param array<string,mixed> $row
     */
    private function processClientRow(array $row, AbacAdminImportOptions $options, AbacAdminImportReport $report): void
    {
        $report->clientsLidos++;

        $srcId = (int) ($row['id'] ?? 0);
        $digits = Normalizer::digits((string) ($row['cpf_cnpj'] ?? ''));
        $nomeKey = Normalizer::normalizeName((string) ($row['nome'] ?? ''));

        if ($digits === '') {
            $report->clientsSemDocumento++;

            if ($nomeKey === '') {
                $this->warn($report, 'Cliente sem documento e sem nome — impossível deduplicar, pulado', [
                    'origem_id' => $srcId,
                ]);

                return;
            }

            $destId = $this->byNomeSemDoc[$nomeKey] ?? null;

            if ($destId !== null) {
                $report->clientsPuladosExistentes++;
                $this->idMap[$srcId] = $destId;

                return;
            }

            if (isset($this->srcSeenNomes[$nomeKey])) {
                $report->duplicadosNaOrigem++;
                $this->warn($report, 'Cliente sem documento com nome repetido na origem — mesclado no primeiro', [
                    'origem_id' => $srcId,
                ]);

                return;
            }

            $this->srcSeenNomes[$nomeKey] = true;
            $destId = $this->insertClient($row, $options, $report);
            $this->byNomeSemDoc[$nomeKey] = $destId;
            $this->idMap[$srcId] = $destId;

            return;
        }

        $destId = $this->byDigits[$digits] ?? null;

        if ($destId !== null) {
            if (isset($this->srcSeenDigits[$digits])) {
                $report->duplicadosNaOrigem++;
                $this->warn($report, 'Documento duplicado dentro da origem — contatos vão para o cliente já migrado', [
                    'origem_id' => $srcId,
                    'destino_id' => $destId,
                ]);
            } else {
                $report->clientsPuladosExistentes++;
            }

            $this->idMap[$srcId] = $destId;
        } else {
            $destId = $this->insertClient($row, $options, $report);
            $this->byDigits[$digits] = $destId;
            $this->idMap[$srcId] = $destId;
        }

        $this->srcSeenDigits[$digits] = true;
    }

    /**
     * Monta a linha do destino: de-para de colunas, status convertido para 1/0 e
     * campos sem coluna preservados em notes com rótulo.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function buildClientData(array $row): array
    {
        $data = [];

        foreach ($this->clientCopyPlan as $src => $dst) {
            $value = $row[$src] ?? null;
            $data[$dst] = $this->truncate('clients', $dst, is_string($value) ? trim($value) : $value);
        }

        if ($this->sourceHasStatus) {
            $status = mb_strtolower(trim((string) ($row['status'] ?? '')));
            // Origem usa texto ('Ativo', 'A', null...); destino é tinyint NOT NULL.
            $data['status'] = in_array($status, ['0', 'i', 'inativo', 'inactive', 'false'], true) ? 0 : 1;
        }

        $notes = [];

        $obs = Normalizer::trimOrNull((string) ($row['obs'] ?? ''));
        if ($obs !== null) {
            $notes[] = $obs;
        }

        $extraEmails = [];
        foreach (self::EXTRA_EMAILS as $col) {
            $email = trim((string) ($row[$col] ?? ''));
            if ($email !== '' && ! in_array($email, $extraEmails, true)) {
                $extraEmails[] = $email;
            }
        }
        if ($extraEmails !== []) {
            $notes[] = 'E-mails adicionais: ' . implode('; ', $extraEmails);
        }

        foreach (self::CLIENT_TO_NOTES as $col => $label) {
            if (! in_array($col, $this->clientNotesPlan, true)) {
                continue;
            }
            $value = Normalizer::trimOrNull((string) ($row[$col] ?? ''));
            if ($value !== null) {
                $notes[] = "{$label}: {$value}";
            }
        }

        if ($notes !== []) {
            $data['notes'] = implode("\n", $notes);
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function insertClient(array $row, AbacAdminImportOptions $options, AbacAdminImportReport $report): int
    {
        $data = $this->buildClientData($row);

        if ($options->dryRun) {
            $destId = --$this->fakeId;
        } else {
            $destId = (int) DB::table('clients')->insertGetId($data);
        }

        $report->clientsCriados++;

        // Semeia o dedup de contatos com os e-mails do próprio cliente migrado.
        $emails = [];
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '') {
            $emails[$email] = true;
        }
        foreach (self::EXTRA_EMAILS as $col) {
            $extra = mb_strtolower(trim((string) ($row[$col] ?? '')));
            if ($extra !== '') {
                $emails[$extra] = true;
            }
        }
        $this->contactKeys[$destId] = ['emails' => $emails, 'names' => []];

        return $destId;
    }

    private function migrateContatos(AbacAdminImportOptions $options, AbacAdminImportReport $report): void
    {
        $this->source()->table($options->sourceContatosTable)
            ->orderBy('id')
            ->chunk($options->chunkSize, function ($rows) use ($options, $report): void {
                $rows = $rows->map(static fn (object $row): array => (array) $row)->all();

                foreach ($rows as $row) {
                    try {
                        $this->processContatoRow($row, $options, $report);
                    } catch (Throwable $e) {
                        $report->erros++;
                        $this->warn($report, 'Falha ao migrar contato — linha pulada', [
                            'origem_id' => $row['id'] ?? null,
                            'erro' => $e->getMessage(),
                        ]);
                    }
                }

                if ($rows !== [] && $options->onChunk !== null) {
                    ($options->onChunk)(count($rows));
                }
            });
    }

    /**
     * @param array<string,mixed> $row
     */
    private function processContatoRow(array $row, AbacAdminImportOptions $options, AbacAdminImportReport $report): void
    {
        $report->contatosLidos++;

        $srcClientId = (int) ($row['client_id'] ?? 0);

        // Regra central: o cliente referenciado precisa existir.
        if (! isset($this->sourceClientIds[$srcClientId])) {
            $report->contatosOrfaos++;
            $this->warn($report, 'Contato órfão — client_id não existe na tabela clients da origem', [
                'origem_id' => $row['id'] ?? null,
                'client_id' => $srcClientId,
            ]);

            return;
        }

        $destId = $this->idMap[$srcClientId] ?? null;

        if ($destId === null) {
            // Cliente existe na origem mas ficou fora desta execução (--limit ou pulado sem chave).
            $report->contatosSemClienteMigrado++;

            return;
        }

        $emails = [];
        foreach (['email', 'email_2'] as $col) {
            $email = mb_strtolower(trim((string) ($row[$col] ?? '')));
            if ($email !== '' && ! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }
        $nomeKey = Normalizer::normalizeName((string) ($row['nome'] ?? ''));

        if ($emails === [] && $nomeKey === '') {
            $report->contatosPuladosSemChave++;

            return;
        }

        $keys = &$this->contactKeysFor($destId);

        if ($emails !== [] && isset($keys['emails'][$emails[0]])) {
            $report->contatosPuladosEmail++;

            return;
        }

        if ($emails === [] && isset($keys['names'][$nomeKey])) {
            $report->contatosPuladosNome++;

            return;
        }

        if (! $options->dryRun) {
            $data = [];
            foreach ($this->contatoCopyPlan as $col) {
                $value = $row[$col] ?? null;
                $data[$col] = $this->truncate('client_contatos', $col, $value);
            }

            DB::table('client_contatos')->insert($data + ['client_id' => $destId]);
        }

        $report->contatosCriados++;

        foreach ($emails as $email) {
            $keys['emails'][$email] = true;
        }
        if ($nomeKey !== '') {
            $keys['names'][$nomeKey] = true;
        }
    }

    /**
     * Conjunto de e-mails/nomes já existentes para o cliente no destino (cache por execução).
     *
     * @return array{emails:array<string,true>,names:array<string,true>}
     */
    private function &contactKeysFor(int $clientId): array
    {
        if (isset($this->contactKeys[$clientId])) {
            return $this->contactKeys[$clientId];
        }

        $emails = [];
        $names = [];

        foreach ($this->emailSeed[$clientId] ?? [] as $email) {
            $emails[$email] = true;
        }

        if ($clientId > 0) {
            $rows = DB::table('client_contatos')
                ->where('client_id', $clientId)
                ->get(['email', 'email_2', 'nome']);

            foreach ($rows as $row) {
                foreach ([$row->email, $row->email_2] as $email) {
                    $email = mb_strtolower(trim((string) $email));
                    if ($email !== '') {
                        $emails[$email] = true;
                    }
                }

                $nomeKey = Normalizer::normalizeName((string) $row->nome);
                if ($nomeKey !== '') {
                    $names[$nomeKey] = true;
                }
            }
        }

        $this->contactKeys[$clientId] = ['emails' => $emails, 'names' => $names];

        return $this->contactKeys[$clientId];
    }

    /**
     * @param array<string,mixed> $context
     */
    private function warn(AbacAdminImportReport $report, string $message, array $context = []): void
    {
        $report->warn($message, $context);
        $this->logger->warning('abac_admin.import.warn: ' . $message, $context);
    }
}
