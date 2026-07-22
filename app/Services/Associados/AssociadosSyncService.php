<?php

namespace App\Services\Associados;

use App\Models\Client;
use App\Models\ClientContato;
use App\Models\ClientEndereco;
use App\Services\Associados\Exceptions\AssociadosSyncException;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sincronização do WordPress dos associados (conexão config('associados.connection'))
 * para o banco default do app.
 *
 * Modelo da origem: o CNPJ da empresa fica em wp_usermeta (meta_key casando com
 * config('associados.cnpj_meta_like')) e cada usuário do WP vinculado ao CNPJ
 * (qualquer linha de usermeta cujo meta_value seja o CNPJ — semântica do script
 * legado) vira um contato do cliente.
 *
 * Regras:
 * - Chave do cliente é sempre o CNPJ, comparado por dígitos normalizados
 *   (clients.document é armazenado FORMATADO e UNIQUE). Variantes formatada e
 *   crua do mesmo CNPJ na origem são fundidas num grupo só.
 * - Cliente existente é ATUALIZADO: associado_abac = true + colunas do
 *   config('associados.meta_map') com valor não-vazio no WP. Valor vazio no WP
 *   nunca sobrescreve dado existente; document/status nunca são tocados.
 * - Cliente inexistente é CRIADO mínimo (document formatado, name da meta
 *   mapeada ou display_name, associado_abac, status, carimbo em obs_cadastro).
 * - Contato: chave aplicativa (client_id, e-mail minúsculo/trimado) — o legado
 *   usava updateOrCreate por client_id + email. user_id fixo
 *   (config('associados.sync.contact_user_id')). Nome = PRIMEIRA meta do
 *   usuário no WP (menor umeta_id — comportamento do script legado, mantido por
 *   decisão de negócio); usuário sem meta nenhuma cai no display_name.
 *   Desvio deliberado do legado: os demais campos do contato (funcao, telefone,
 *   obs...) NÃO são anulados no update — o legado sobrescrevia com null campos
 *   preenchidos à mão no CRUD.
 * - Endereço: metas de config('associados.endereco_meta_map') viram o endereço
 *   tipo "principal" do cliente em client_enderecos (cria se não existir,
 *   atualiza campo a campo; vazio nunca sobrescreve; NOT NULL vira '' como no
 *   rm:import).
 * - Roda com Client::withoutEvents para não inundar client_audit_logs; a trilha
 *   de auditoria da carga é o canal de log 'associados'.
 * - Idempotente: re-executar sem mudança na origem não grava nada.
 */
class AssociadosSyncService
{
    /** Tamanho dos lotes de whereIn contra o banco remoto. */
    private const WHEREIN_BATCH = 500;

    /**
     * Colunas que o meta_map nunca pode apontar: identidade/chave, flags que o
     * próprio sync gerencia e campos de controle/auditoria. Além desta lista,
     * o preflight só aceita colunas de tipo textual (char/varchar/text).
     */
    private const FORBIDDEN_TARGETS = [
        'id', 'document', 'status', 'associado_abac', 'associado_sinac',
        'created_by', 'updated_by', 'created_at', 'updated_at', 'cod_omie',
    ];

    private readonly string $sourceConnection;

    private string $cnpjMetaLike = '%cnpj_associada%';

    /** @var array<string,string> meta_key do WP => coluna de clients (validado no preflight) */
    private array $metaPlan = [];

    /** @var array<string,string> meta_key do WP => campo de client_enderecos (validado no preflight) */
    private array $enderecoPlan = [];

    /** @var list<string> meta_keys ignoradas no aviso de "não mapeada" */
    private array $metaIgnore = [];

    private int $contactUserId = 1;

    /** @var array<string,int> dígitos do documento => clients.id */
    private array $byDigits = [];

    /** @var array<string,array{associado_abac:bool,cols:array<string,mixed>}> estado atual por dígitos */
    private array $clientState = [];

    /** @var array<string,list<int>> dígitos => user_ids do WP (ordenados) */
    private array $usersPorCnpj = [];

    /** @var array<int,object{ID:int|string,user_email:?string,display_name:?string}> */
    private array $wpUsers = [];

    /** @var array<int,?string> user_id => valor da primeira meta (menor umeta_id) */
    private array $primeiraMeta = [];

    /** @var array<int,array<string,string>> user_id => [meta_key mapeada => valor] */
    private array $metasPorUser = [];

    /** @var array<string,array<string,int>> limites de varchar do destino por tabela */
    private array $limits = ['clients' => [], 'client_contatos' => []];

    private bool $hasObsCadastro = false;

    private int $fakeId = 0;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?string $sourceConnection = null,
    ) {
        $this->sourceConnection = $sourceConnection
            ?? (string) config('associados.connection', 'pgsql-associado');
    }

    public function run(AssociadosSyncOptions $options): AssociadosSyncReport
    {
        $report = new AssociadosSyncReport($options->maxWarningSamples);
        $this->resetState();
        $this->loadConfig();

        $this->logger->info('associados.sync.start', [
            'dry_run' => $options->dryRun,
            'limit' => $options->limit,
            'chunk' => $options->chunkSize,
        ]);

        $this->preflight($report);
        $this->loadClientState($report);

        $grupos = $this->loadCnpjGroups($options, $report);
        $this->mapUsersToCnpjs($grupos, $report);
        $this->loadUserData($report);

        Client::withoutEvents(function () use ($grupos, $options, $report): void {
            $this->processarCnpjs($grupos, $options, $report);
        });

        $this->logger->info('associados.sync.done', $report->toArray());

        return $report;
    }

    /** Total de grupos de CNPJ que uma execução com essas opções processaria (para a progress bar). */
    public function countCnpjs(AssociadosSyncOptions $options): int
    {
        $this->resetState();
        $this->loadConfig();
        $this->checkSourceTables();

        return count($this->loadCnpjGroups($options, new AssociadosSyncReport));
    }

    /**
     * Lista as meta_keys dos usuários associados, com contagem e status, sem gravar nada.
     * Alimenta o preenchimento de config('associados.meta_map') / meta_ignore.
     *
     * @return list<array{meta_key:string,linhas:int,usuarios:int,status:string}>
     */
    public function discover(): array
    {
        $this->resetState();
        $this->loadConfig();
        $this->checkSourceTables();

        $grupos = $this->loadCnpjGroups(new AssociadosSyncOptions, new AssociadosSyncReport);
        $this->mapUsersToCnpjs($grupos, new AssociadosSyncReport);

        $userIds = $this->allUserIds();
        $stats = [];

        foreach (array_chunk($userIds, self::WHEREIN_BATCH) as $batch) {
            $rows = $this->source()->table('wp_usermeta')
                ->whereIn('user_id', $batch)
                ->selectRaw('meta_key, COUNT(*) as linhas, COUNT(DISTINCT user_id) as usuarios')
                ->groupBy('meta_key')
                ->get();

            foreach ($rows as $row) {
                $key = (string) $row->meta_key;
                $stats[$key]['linhas'] = ($stats[$key]['linhas'] ?? 0) + (int) $row->linhas;
                $stats[$key]['usuarios'] = ($stats[$key]['usuarios'] ?? 0) + (int) $row->usuarios;
            }
        }

        $out = [];
        foreach ($stats as $key => $agg) {
            $out[] = [
                'meta_key' => $key,
                'linhas' => $agg['linhas'],
                'usuarios' => $agg['usuarios'],
                'status' => $this->metaStatus($key),
            ];
        }

        usort($out, static fn (array $a, array $b): int => $b['linhas'] <=> $a['linhas']);

        return $out;
    }

    private function metaStatus(string $metaKey): string
    {
        if (isset($this->metaPlan[$metaKey]) || array_key_exists($metaKey, (array) config('associados.meta_map', []))) {
            return 'mapeada';
        }
        if (isset($this->enderecoPlan[$metaKey]) || array_key_exists($metaKey, (array) config('associados.endereco_meta_map', []))) {
            return 'mapeada (endereço)';
        }
        // LIKE do MySQL é case-insensitive; espelhamos minusculizando os dois lados.
        if (Str::is(mb_strtolower(str_replace('%', '*', $this->cnpjMetaLike)), mb_strtolower($metaKey))) {
            return 'cnpj (chave)';
        }
        if (in_array($metaKey, $this->metaIgnore, true)) {
            return 'ignorada';
        }

        return 'NÃO MAPEADA';
    }

    private function resetState(): void
    {
        $this->metaPlan = [];
        $this->enderecoPlan = [];
        $this->byDigits = [];
        $this->clientState = [];
        $this->usersPorCnpj = [];
        $this->wpUsers = [];
        $this->primeiraMeta = [];
        $this->metasPorUser = [];
        $this->limits = ['clients' => [], 'client_contatos' => [], 'client_enderecos' => []];
        $this->hasObsCadastro = false;
        $this->fakeId = 0;
    }

    private function loadConfig(): void
    {
        $this->cnpjMetaLike = (string) config('associados.cnpj_meta_like', '%cnpj_associada%');
        $this->metaIgnore = array_values((array) config('associados.meta_ignore', []));
        $this->contactUserId = (int) config('associados.sync.contact_user_id', 1);
    }

    private function source(): \Illuminate\Database\ConnectionInterface
    {
        return DB::connection($this->sourceConnection);
    }

    private function checkSourceTables(): void
    {
        try {
            $srcSchema = Schema::connection($this->sourceConnection);

            foreach (['wp_users', 'wp_usermeta'] as $table) {
                if (! $srcSchema->hasTable($table)) {
                    throw AssociadosSyncException::tabelaAusente($this->sourceConnection, $table);
                }
            }
        } catch (AssociadosSyncException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw AssociadosSyncException::conexaoIndisponivel($this->sourceConnection, $e->getMessage());
        }
    }

    /**
     * Valida tabelas/colunas essenciais e o meta_map contra o schema vivo do destino
     * (que diverge das migrations — só colunas que existem de verdade entram no plano).
     */
    private function preflight(AssociadosSyncReport $report): void
    {
        $this->checkSourceTables();

        foreach (['clients', 'client_contatos'] as $table) {
            if (! Schema::hasTable($table)) {
                throw AssociadosSyncException::tabelaAusente('default', $table);
            }
        }

        $dstClientCols = Schema::getColumnListing('clients');

        foreach (['document', 'name', 'associado_abac'] as $col) {
            if (! in_array($col, $dstClientCols, true)) {
                throw AssociadosSyncException::colunaObrigatoriaAusente('default', 'clients', $col);
            }
        }

        $dstContatoCols = Schema::getColumnListing('client_contatos');
        foreach (['client_id', 'email', 'nome'] as $col) {
            if (! in_array($col, $dstContatoCols, true)) {
                throw AssociadosSyncException::colunaObrigatoriaAusente('default', 'client_contatos', $col);
            }
        }

        $this->hasObsCadastro = in_array('obs_cadastro', $dstClientCols, true);

        // Só colunas textuais são elegíveis: meta de WP em coluna int/date/boolean
        // nunca estabiliza a comparação de mudança (update fantasma a cada run)
        // ou estoura no strict mode.
        $clientTextCols = $this->textualColumns('clients');

        $descartadas = [];
        foreach ((array) config('associados.meta_map', []) as $metaKey => $column) {
            $metaKey = (string) $metaKey;
            $column = (string) $column;

            if (in_array($column, self::FORBIDDEN_TARGETS, true) || ! in_array($column, $clientTextCols, true)) {
                $descartadas[] = "clients.{$column} (meta {$metaKey})";

                continue;
            }

            $this->metaPlan[$metaKey] = $column;
        }

        $enderecoMap = (array) config('associados.endereco_meta_map', []);
        if ($enderecoMap !== []) {
            if (! Schema::hasTable('client_enderecos')) {
                $this->warn($report, 'endereco_meta_map configurado mas a tabela client_enderecos não existe — endereços não serão sincronizados');
            } else {
                $enderecoTextCols = $this->textualColumns('client_enderecos');

                foreach ($enderecoMap as $metaKey => $field) {
                    $metaKey = (string) $metaKey;
                    $field = (string) $field;

                    if (in_array($field, ['id', 'client_id', 'tipo'], true) || ! in_array($field, $enderecoTextCols, true)) {
                        $descartadas[] = "client_enderecos.{$field} (meta {$metaKey})";

                        continue;
                    }

                    $this->enderecoPlan[$metaKey] = $field;
                }

                $this->limits['client_enderecos'] = $this->columnLimits('client_enderecos');
            }
        }

        if ($descartadas !== []) {
            $report->colunasDescartadas = $descartadas;
            $this->warn($report, 'Entradas do meta_map/endereco_meta_map descartadas — coluna inexistente, proibida ou não-textual no destino', [
                'colunas' => $descartadas,
            ]);
        }

        $this->limits['clients'] = $this->columnLimits('clients');
        $this->limits['client_contatos'] = $this->columnLimits('client_contatos');
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

    /**
     * @return list<string> colunas de tipo textual (char/varchar/text*) da tabela
     */
    private function textualColumns(string $table): array
    {
        $cols = [];

        foreach (Schema::getColumns($table) as $col) {
            if (preg_match('/char|text/i', (string) ($col['type'] ?? '')) === 1) {
                $cols[] = (string) $col['name'];
            }
        }

        return $cols;
    }

    /**
     * Fase A — estado atual do destino, indexado por dígitos do documento
     * (o banco guarda formatado; a comparação é sempre por dígitos).
     */
    private function loadClientState(AssociadosSyncReport $report): void
    {
        $mappedCols = array_values(array_unique(array_values($this->metaPlan)));

        $rows = DB::table('clients')
            ->select(array_merge(['id', 'document', 'associado_abac'], $mappedCols))
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $row) {
            $digits = Normalizer::digits((string) $row->document);

            if ($digits === '') {
                continue;
            }

            if (isset($this->byDigits[$digits])) {
                $this->warn($report, 'Documento duplicado já existente no destino — usando o menor id', [
                    'documento' => $digits,
                    'id_usado' => $this->byDigits[$digits],
                    'id_ignorado' => (int) $row->id,
                ]);

                continue;
            }

            $this->byDigits[$digits] = (int) $row->id;

            $cols = [];
            foreach ($mappedCols as $col) {
                $cols[$col] = $row->{$col};
            }

            $this->clientState[$digits] = [
                'associado_abac' => (bool) $row->associado_abac,
                'cols' => $cols,
            ];
        }
    }

    /**
     * Fase B — meta_values distintos de CNPJ, validados e agrupados por dígitos
     * (variantes formatada e crua do mesmo CNPJ fundem num grupo só).
     *
     * @return array<string,list<string>> dígitos => variantes cruas na origem
     */
    private function loadCnpjGroups(AssociadosSyncOptions $options, AssociadosSyncReport $report): array
    {
        $raws = $this->source()->table('wp_usermeta')
            ->where('meta_key', 'like', $this->cnpjMetaLike)
            ->whereNotNull('meta_value')
            ->where('meta_value', '<>', '')
            ->distinct()
            ->orderBy('meta_value')
            ->pluck('meta_value');

        $grupos = [];

        foreach ($raws as $raw) {
            $raw = (string) $raw;
            $report->cnpjsLidos++;

            $digits = Normalizer::digits($raw);

            if (! Normalizer::isValidDoc($digits)) {
                $report->cnpjsInvalidos++;
                $this->warn($report, 'CNPJ inválido no WordPress — pulado', ['meta_value' => $raw]);

                continue;
            }

            $grupos[$digits][] = $raw;
        }

        foreach ($grupos as $variantes) {
            if (count($variantes) > 1) {
                $report->cnpjsAgrupados++;
            }
        }

        // --limit corta GRUPOS já agrupados: variantes do mesmo CNPJ nunca se separam.
        if ($options->limit !== null) {
            $grupos = array_slice($grupos, 0, max(0, $options->limit), true);
        }

        return $grupos;
    }

    /**
     * Fase C — usuários do WP vinculados a cada CNPJ: qualquer linha de usermeta
     * cujo meta_value seja o CNPJ (semântica do script legado).
     *
     * @param array<string,list<string>> $grupos
     */
    private function mapUsersToCnpjs(array $grupos, AssociadosSyncReport $report): void
    {
        $variantToDigits = [];
        foreach ($grupos as $digits => $variantes) {
            foreach ($variantes as $variante) {
                $variantToDigits[$variante] = (string) $digits;
            }
        }

        $sets = [];

        // array_keys converte string numérica canônica em int; o whereIn precisa
        // de STRINGS, senão o MySQL cai em comparação numérica e casa prefixos
        // (ex.: '12345678000195x' casaria com 12345678000195).
        foreach (array_chunk(array_map(strval(...), array_keys($variantToDigits)), self::WHEREIN_BATCH) as $batch) {
            $rows = $this->source()->table('wp_usermeta')
                ->whereIn('meta_value', $batch)
                ->get(['user_id', 'meta_value']);

            foreach ($rows as $row) {
                $value = (string) $row->meta_value;
                $digits = $variantToDigits[$value] ?? Normalizer::digits($value);

                if (! isset($grupos[$digits])) {
                    continue;
                }

                $sets[$digits][(int) $row->user_id] = true;
            }
        }

        foreach ($sets as $digits => $set) {
            $ids = array_keys($set);
            sort($ids);
            $this->usersPorCnpj[(string) $digits] = $ids;
        }
    }

    /** @return list<int> */
    private function allUserIds(): array
    {
        $ids = [];
        foreach ($this->usersPorCnpj as $userIds) {
            foreach ($userIds as $id) {
                $ids[$id] = true;
            }
        }

        $ids = array_keys($ids);
        sort($ids);

        return $ids;
    }

    /**
     * Fase D — dados dos usuários em lote: wp_users, a "primeira meta" (nome do
     * contato, comportamento legado), as metas mapeadas e o censo das não mapeadas.
     */
    private function loadUserData(AssociadosSyncReport $report): void
    {
        $mappedKeys = array_values(array_unique(array_merge(
            array_keys($this->metaPlan),
            array_keys($this->enderecoPlan),
        )));

        foreach (array_chunk($this->allUserIds(), self::WHEREIN_BATCH) as $batch) {
            $users = $this->source()->table('wp_users')
                ->whereIn('ID', $batch)
                ->get(['ID', 'user_email', 'display_name']);

            foreach ($users as $user) {
                $this->wpUsers[(int) $user->ID] = $user;
            }

            // Nome legado: a primeira linha de usermeta do usuário (menor umeta_id).
            $mins = $this->source()->table('wp_usermeta')
                ->whereIn('user_id', $batch)
                ->groupBy('user_id')
                ->selectRaw('MIN(umeta_id) as umeta_id')
                ->pluck('umeta_id');

            if ($mins->isNotEmpty()) {
                $rows = $this->source()->table('wp_usermeta')
                    ->whereIn('umeta_id', $mins->all())
                    ->get(['user_id', 'meta_value']);

                foreach ($rows as $row) {
                    $this->primeiraMeta[(int) $row->user_id] = Normalizer::trimOrNull((string) $row->meta_value);
                }
            }

            if ($mappedKeys !== []) {
                $rows = $this->source()->table('wp_usermeta')
                    ->whereIn('user_id', $batch)
                    ->whereIn('meta_key', $mappedKeys)
                    ->orderBy('umeta_id')
                    ->get(['user_id', 'meta_key', 'meta_value']);

                foreach ($rows as $row) {
                    // Meta repetida para o mesmo usuário: vence a de menor umeta_id.
                    $this->metasPorUser[(int) $row->user_id][(string) $row->meta_key] ??= (string) $row->meta_value;
                }
            }

            // Censo de metas sem de-para (fora do ignore e da chave de CNPJ).
            $query = $this->source()->table('wp_usermeta')
                ->whereIn('user_id', $batch)
                ->where('meta_key', 'not like', $this->cnpjMetaLike)
                ->selectRaw('meta_key, COUNT(*) as c')
                ->groupBy('meta_key');

            if ($mappedKeys !== []) {
                $query->whereNotIn('meta_key', $mappedKeys);
            }
            if ($this->metaIgnore !== []) {
                $query->whereNotIn('meta_key', $this->metaIgnore);
            }

            foreach ($query->get() as $row) {
                $key = (string) $row->meta_key;
                $report->metasNaoMapeadas[$key] = ($report->metasNaoMapeadas[$key] ?? 0) + (int) $row->c;
            }
        }

        if ($report->metasNaoMapeadas !== []) {
            arsort($report->metasNaoMapeadas);
            $this->warn($report, 'Metas do WP sem de-para em config/associados.php — rode --discover e preencha meta_map/meta_ignore', [
                'metas' => array_slice($report->metasNaoMapeadas, 0, 30, true),
            ]);
        }
    }

    /**
     * Fase E — processa os grupos de CNPJ em chunks, com preload dos contatos
     * existentes de cada chunk (uma query por chunk, nada de N+1).
     *
     * @param array<string,list<string>> $grupos
     */
    private function processarCnpjs(array $grupos, AssociadosSyncOptions $options, AssociadosSyncReport $report): void
    {
        foreach (array_chunk($grupos, $options->chunkSize, preserve_keys: true) as $chunk) {
            $digitsList = array_map(strval(...), array_keys($chunk));
            $contactState = $this->preloadContatos($digitsList);
            $enderecoState = $this->enderecoPlan !== [] ? $this->preloadEnderecos($digitsList) : [];

            foreach (array_keys($chunk) as $digits) {
                $digits = (string) $digits;
                $snapshot = $report->counters();

                try {
                    if ($options->dryRun) {
                        $this->processarCnpj($digits, $options, $report, $contactState, $enderecoState);
                    } else {
                        DB::transaction(function () use ($digits, $options, $report, &$contactState, &$enderecoState): void {
                            $this->processarCnpj($digits, $options, $report, $contactState, $enderecoState);
                        });
                    }
                } catch (Throwable $e) {
                    // O rollback desfez as escritas do CNPJ; desfazemos também os
                    // contadores já incrementados (warnings ficam — são amostras).
                    $report->restoreCounters($snapshot);
                    $report->erros++;
                    $this->warn($report, 'Falha ao sincronizar CNPJ — pulado', [
                        'cnpj' => $digits,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            if ($chunk !== [] && $options->onChunk !== null) {
                ($options->onChunk)(count($chunk));
            }
        }
    }

    /**
     * Contatos já existentes dos clientes do chunk, indexados por e-mail normalizado
     * (duplicado no destino: vence o de menor id, os demais ficam intocados).
     *
     * @param list<string> $digitsList
     * @return array<int,array<string,array{id:?int,nome:?string}>>
     */
    private function preloadContatos(array $digitsList): array
    {
        $clientIds = [];
        foreach ($digitsList as $digits) {
            $id = $this->byDigits[$digits] ?? null;
            if ($id !== null && $id > 0) {
                $clientIds[] = $id;
            }
        }

        $state = array_fill_keys($clientIds, []);

        foreach (array_chunk($clientIds, self::WHEREIN_BATCH) as $batch) {
            $rows = DB::table('client_contatos')
                ->whereIn('client_id', $batch)
                ->orderBy('id')
                ->get(['id', 'client_id', 'email', 'nome']);

            foreach ($rows as $row) {
                $email = mb_strtolower(trim((string) $row->email));

                if ($email === '' || isset($state[(int) $row->client_id][$email])) {
                    continue;
                }

                $state[(int) $row->client_id][$email] = [
                    'id' => (int) $row->id,
                    'nome' => Normalizer::trimOrNull((string) $row->nome),
                ];
            }
        }

        return $state;
    }

    /**
     * Endereço "principal" já existente dos clientes do chunk (mais de um: vence
     * o de menor id).
     *
     * @param list<string> $digitsList
     * @return array<int,array{id:int,fields:array<string,mixed>}>
     */
    private function preloadEnderecos(array $digitsList): array
    {
        $clientIds = [];
        foreach ($digitsList as $digits) {
            $id = $this->byDigits[$digits] ?? null;
            if ($id !== null && $id > 0) {
                $clientIds[] = $id;
            }
        }

        $fields = array_values(array_unique(array_values($this->enderecoPlan)));
        $state = [];

        foreach (array_chunk($clientIds, self::WHEREIN_BATCH) as $batch) {
            $rows = DB::table('client_enderecos')
                ->whereIn('client_id', $batch)
                ->where('tipo', 'principal')
                ->orderBy('id')
                ->get(array_merge(['id', 'client_id'], $fields));

            foreach ($rows as $row) {
                $clientId = (int) $row->client_id;

                if (isset($state[$clientId])) {
                    continue;
                }

                $campos = [];
                foreach ($fields as $field) {
                    $campos[$field] = $row->{$field};
                }

                $state[$clientId] = ['id' => (int) $row->id, 'fields' => $campos];
            }
        }

        return $state;
    }

    /**
     * @param array<int,array<string,array{id:?int,nome:?string}>> $contactState
     * @param array<int,array{id:int,fields:array<string,mixed>}> $enderecoState
     */
    private function processarCnpj(
        string $digits,
        AssociadosSyncOptions $options,
        AssociadosSyncReport $report,
        array &$contactState,
        array &$enderecoState,
    ): void {
        $userIds = $this->usersPorCnpj[$digits] ?? [];
        $resolved = $this->resolveMetas($digits, $userIds, $report, array_merge(
            array_keys($this->metaPlan),
            array_keys($this->enderecoPlan),
        ));
        $metas = array_intersect_key($resolved, $this->metaPlan);
        $enderecoMetas = array_intersect_key($resolved, $this->enderecoPlan);

        $clientId = $this->byDigits[$digits] ?? null;

        if ($clientId !== null) {
            $payload = $this->buildUpdatePayload($digits, $metas);

            if ($payload === []) {
                $report->clientsJaSincronizados++;
            } else {
                if (! $options->dryRun) {
                    Client::whereKey($clientId)->update($payload);
                }

                $report->clientsAtualizados++;

                foreach ($payload as $col => $value) {
                    if ($col === 'associado_abac') {
                        $this->clientState[$digits]['associado_abac'] = true;
                    } else {
                        $this->clientState[$digits]['cols'][$col] = $value;
                    }
                }
            }
        } else {
            $clientId = $this->criarClient($digits, $metas, $userIds, $options, $report);
            $contactState[$clientId] = [];
        }

        if ($this->enderecoPlan !== []) {
            $this->sincronizarEndereco($clientId, $enderecoMetas, $options, $report, $enderecoState);
        }

        $this->sincronizarContatos($clientId, $digits, $userIds, $options, $report, $contactState);
    }

    /**
     * Resolve o valor de cada meta mapeada para o CNPJ: usuários em ordem
     * crescente, vence o primeiro valor não-vazio; divergência vira warning.
     *
     * @param list<int> $userIds
     * @param list<string> $metaKeys
     * @return array<string,string> meta_key => valor resolvido
     */
    private function resolveMetas(string $digits, array $userIds, AssociadosSyncReport $report, array $metaKeys): array
    {
        $result = [];

        foreach ($metaKeys as $metaKey) {
            $values = [];

            foreach ($userIds as $userId) {
                $value = Normalizer::trimOrNull((string) ($this->metasPorUser[$userId][$metaKey] ?? ''));
                if ($value !== null && ! in_array($value, $values, true)) {
                    $values[] = $value;
                }
            }

            if ($values === []) {
                continue;
            }

            if (count($values) > 1) {
                $report->conflitosDeMeta++;
                $this->warn($report, 'Valores divergentes da mesma meta entre usuários do CNPJ — prevalece o do menor user_id', [
                    'cnpj' => $digits,
                    'meta_key' => $metaKey,
                    'valores' => array_slice($values, 0, 5),
                ]);
            }

            $result[$metaKey] = $values[0];
        }

        return $result;
    }

    /**
     * Payload de update do cliente existente: associado_abac quando ainda não é,
     * e só as colunas mapeadas cujo valor no WP difere do atual (vazio nunca
     * sobrescreve; document/status nunca entram).
     *
     * @param array<string,string> $metas
     * @return array<string,mixed>
     */
    private function buildUpdatePayload(string $digits, array $metas): array
    {
        $current = $this->clientState[$digits];
        $payload = [];

        if (! $current['associado_abac']) {
            $payload['associado_abac'] = true;
        }

        foreach ($metas as $metaKey => $value) {
            $column = $this->metaPlan[$metaKey];
            $value = $this->truncate('clients', $column, $value);

            $atual = $current['cols'][$column] ?? null;
            if (Normalizer::trimOrNull((string) $atual) !== $value) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    /**
     * @param array<string,string> $metas
     * @param list<int> $userIds
     */
    private function criarClient(
        string $digits,
        array $metas,
        array $userIds,
        AssociadosSyncOptions $options,
        AssociadosSyncReport $report,
    ): int {
        $payload = [];

        foreach ($metas as $metaKey => $value) {
            $column = $this->metaPlan[$metaKey];
            $payload[$column] = $this->truncate('clients', $column, $value);
        }

        $payload['document'] = Normalizer::limit(Normalizer::formatCpfCnpj($digits), 20);
        $payload['associado_abac'] = true;
        $payload['status'] = true;

        if (Normalizer::trimOrNull((string) ($payload['name'] ?? '')) === null) {
            $payload['name'] = $this->fallbackName($digits, $userIds, $report);
        }
        $payload['name'] = $this->truncate('clients', 'name', $payload['name']);

        if ($this->hasObsCadastro) {
            $payload['obs_cadastro'] = sprintf('Importado do WordPress dos associados em %s.', now()->format('d/m/Y'));
        }

        if ($options->dryRun) {
            $clientId = --$this->fakeId;
        } else {
            $clientId = (int) Client::create($payload)->id;
        }

        $report->clientsCriados++;
        $this->byDigits[$digits] = $clientId;
        $this->clientState[$digits] = [
            'associado_abac' => true,
            'cols' => array_diff_key($payload, ['document' => 1, 'associado_abac' => 1, 'status' => 1, 'obs_cadastro' => 1, 'name' => 1]),
        ];

        return $clientId;
    }

    /** @param list<int> $userIds */
    private function fallbackName(string $digits, array $userIds, AssociadosSyncReport $report): string
    {
        foreach ($userIds as $userId) {
            $display = Normalizer::trimOrNull((string) ($this->wpUsers[$userId]->display_name ?? ''));
            if ($display !== null) {
                return $display;
            }
        }

        $this->warn($report, 'Cliente novo sem nome no WP — usando o próprio CNPJ como name', ['cnpj' => $digits]);

        return Normalizer::formatCpfCnpj($digits);
    }

    /**
     * Contatos do CNPJ: chave aplicativa (client_id, e-mail normalizado), como o
     * legado (updateOrCreate por client_id + email).
     *
     * @param list<int> $userIds
     * @param array<int,array<string,array{id:?int,nome:?string}>> $contactState
     */
    private function sincronizarContatos(
        int $clientId,
        string $digits,
        array $userIds,
        AssociadosSyncOptions $options,
        AssociadosSyncReport $report,
        array &$contactState,
    ): void {
        // Um usuário por e-mail: contas WP duplicadas com o mesmo e-mail fariam o
        // contato flip-flopar de nome a cada execução (cada usuário regravando o
        // seu). Vence o menor user_id, coerente com resolveMetas.
        $porEmail = [];

        foreach ($userIds as $userId) {
            $user = $this->wpUsers[$userId] ?? null;

            if ($user === null) {
                $report->usuariosOrfaos++;
                $this->warn($report, 'Usermeta aponta para usuário inexistente em wp_users — contato pulado', [
                    'cnpj' => $digits,
                    'user_id' => $userId,
                ]);

                continue;
            }

            $email = mb_strtolower(trim((string) $user->user_email));

            if ($email === '') {
                $report->usuariosSemEmail++;

                continue;
            }

            if (isset($porEmail[$email])) {
                $this->warn($report, 'Usuários WP distintos com o mesmo e-mail no CNPJ — prevalece o de menor user_id', [
                    'cnpj' => $digits,
                    'email' => $email,
                    'user_id_usado' => $porEmail[$email],
                    'user_id_ignorado' => $userId,
                ]);

                continue;
            }

            $porEmail[$email] = $userId;
        }

        foreach ($porEmail as $email => $userId) {
            $email = (string) $email;
            $user = $this->wpUsers[$userId];

            $nome = $this->primeiraMeta[$userId]
                ?? Normalizer::trimOrNull((string) $user->display_name);
            $nome = $this->truncate('client_contatos', 'nome', $nome);

            $existing = $contactState[$clientId][$email] ?? null;

            if ($existing === null) {
                if (! $options->dryRun) {
                    ClientContato::create([
                        'client_id' => $clientId,
                        'user_id' => $this->contactUserId,
                        'nome' => $nome,
                        'email' => $email,
                        'unlock_whatsApp' => false,
                    ]);
                }

                $report->contatosCriados++;
                $contactState[$clientId][$email] = ['id' => null, 'nome' => $nome];
            } elseif ($nome !== null && $nome !== $existing['nome']) {
                if (! $options->dryRun) {
                    // Update pelo id pré-carregado (uma query, e imune a diferença
                    // de caixa entre o e-mail gravado e o normalizado).
                    if ($existing['id'] !== null) {
                        ClientContato::whereKey($existing['id'])
                            ->update(['user_id' => $this->contactUserId, 'nome' => $nome]);
                    } else {
                        ClientContato::updateOrCreate(
                            ['client_id' => $clientId, 'email' => $email],
                            ['user_id' => $this->contactUserId, 'nome' => $nome],
                        );
                    }
                }

                $report->contatosAtualizados++;
                $contactState[$clientId][$email]['nome'] = $nome;
            } else {
                $report->contatosSemMudanca++;
            }
        }
    }

    /**
     * Cria/atualiza o endereço "principal" do cliente com as metas de endereço
     * (padrão do rm:import: colunas NOT NULL viram '', complemento aceita null;
     * vazio no WP nunca sobrescreve campo preenchido).
     *
     * @param array<string,string> $enderecoMetas
     * @param array<int,array{id:int,fields:array<string,mixed>}> $enderecoState
     */
    private function sincronizarEndereco(
        int $clientId,
        array $enderecoMetas,
        AssociadosSyncOptions $options,
        AssociadosSyncReport $report,
        array &$enderecoState,
    ): void {
        $values = [];

        foreach ($enderecoMetas as $metaKey => $value) {
            $field = $this->enderecoPlan[$metaKey];

            if ($field === 'cep') {
                $value = Normalizer::formatCep($value) ?? $value;
            }

            $values[$field] = $this->truncate('client_enderecos', $field, $value);
        }

        if ($values === []) {
            return;
        }

        $existing = $enderecoState[$clientId] ?? null;

        if ($existing === null) {
            // Mínimo para valer a pena criar (mesmo critério do rm:import).
            if (($values['rua'] ?? '') === '' && ($values['cep'] ?? '') === '' && ($values['municipio'] ?? '') === '') {
                return;
            }

            $data = $values + [
                'client_id' => $clientId,
                'tipo' => 'principal',
                'cep' => '',
                'rua' => '',
                'numero' => '',
                'complemento' => null,
                'bairro' => '',
                'pais' => '',
                'estado' => '',
                'cod_ibge' => '',
                'municipio' => '',
            ];

            $enderecoId = 0;
            if (! $options->dryRun) {
                $enderecoId = (int) ClientEndereco::create($data)->id;
            }

            $report->enderecosCriados++;
            $enderecoState[$clientId] = ['id' => $enderecoId, 'fields' => $values];

            return;
        }

        $payload = [];
        foreach ($values as $field => $value) {
            $atual = Normalizer::trimOrNull((string) ($existing['fields'][$field] ?? ''));
            if ($atual !== $value) {
                $payload[$field] = $value;
            }
        }

        if ($payload === []) {
            $report->enderecosSemMudanca++;

            return;
        }

        if (! $options->dryRun && $existing['id'] > 0) {
            ClientEndereco::whereKey($existing['id'])->update($payload);
        }

        $report->enderecosAtualizados++;
        $enderecoState[$clientId]['fields'] = array_merge($existing['fields'], $payload);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function warn(AssociadosSyncReport $report, string $message, array $context = []): void
    {
        $report->warn($message, $context);
        $this->logger->warning('associados.sync.warn: ' . $message, $context);
    }
}
