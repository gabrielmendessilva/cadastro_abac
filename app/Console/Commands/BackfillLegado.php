<?php

namespace App\Console\Commands;

use App\Models\Lista\Regional;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Devolve às suas colunas os três campos do legado `abac_admin` que a importação
 * anterior achatou dentro de `clients.notes` como texto livre.
 *
 * A importação original (AbacAdminImportService::CLIENT_TO_NOTES) jogou no
 * `notes` tudo que não tinha coluna de destino, gerando linhas como:
 *
 *     Contato (admin): SEBASTIAO CIRELLI
 *     Regional: SUDESTE
 *     Associado: S
 *
 * Agora que as colunas existem (migration 2026_07_21_000040), lemos direto do
 * banco legado — fonte autoritativa — em vez de fazer parse desse texto:
 *
 *   - `regional`  -> `regional_id`, resolvido contra a tabela de domínio `regionais`
 *   - `associado` -> `associado_abac` ('S' = true, 'N' = false, vazio = não mexe)
 *   - `contato_name_admin` -> coluna homônima
 *
 * Só depois disso o `notes` é limpo, e apenas nas linhas cujo conteúdo é
 * exclusivamente esses rótulos — qualquer texto genuíno é preservado.
 *
 * Idempotente: a segunda execução não altera nada.
 */
class BackfillLegado extends Command
{
    protected $signature = 'clients:backfill-legado
        {--dry-run : Não grava nada; só relata o que seria feito}';

    protected $description = 'Devolve regional_id, associado_abac e contato_name_admin do banco legado abac_admin para as colunas de clients';

    /** Rótulos que a importação anterior escreveu no `notes`. */
    private const ROTULOS_LEGADO = ['Contato (admin)', 'Regional', 'Associado'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Origem: [abac_admin] %s — Destino: [%s] %s',
            (string) config('database.connections.abac_admin.database'),
            (string) config('database.default'),
            (string) config('database.connections.'.config('database.default').'.database'),
        ));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        $regionais = $this->mapaRegionais();
        $legado = $this->carregarLegado();

        if ($legado === []) {
            $this->error('Nenhum registro lido do banco legado. Verifique as credenciais ABAC_ADMIN_DB_* no .env.');

            return self::FAILURE;
        }

        $metricas = [
            'clientes lidos do legado' => count($legado),
            'regional_id preenchido' => 0,
            'associado_abac preenchido' => 0,
            'contato_name_admin preenchido' => 0,
            'notes limpo' => 0,
            'já estavam corretos' => 0,
            'sem correspondência no legado' => 0,
        ];

        $regionaisNaoCasadas = [];

        DB::transaction(function () use ($legado, $regionais, $dryRun, &$metricas, &$regionaisNaoCasadas) {
            foreach (DB::table('clients')->select('id', 'document', 'notes', 'regional_id', 'associado_abac', 'contato_name_admin')->cursor() as $client) {
                $chave = Normalizer::digits($client->document);
                $origem = $legado[$chave] ?? null;

                if ($origem === null) {
                    $metricas['sem correspondência no legado']++;

                    continue;
                }

                $update = [];
                // Enquanto algo desta linha não tiver destino, o `notes` continua
                // sendo a única cópia do dado — não pode ser limpo.
                $perdaPendente = false;

                if ($client->regional_id === null && $origem->regional !== null && $origem->regional !== '') {
                    $slug = $this->normalizarNome($origem->regional);

                    if (isset($regionais[$slug])) {
                        $update['regional_id'] = $regionais[$slug];
                        $metricas['regional_id preenchido']++;
                    } else {
                        $perdaPendente = true;
                        $regionaisNaoCasadas[$origem->regional] = ($regionaisNaoCasadas[$origem->regional] ?? 0) + 1;
                    }
                }

                $associado = $this->interpretarAssociado($origem->associado);

                if ($associado !== null && (bool) $client->associado_abac !== $associado) {
                    $update['associado_abac'] = $associado;
                    $metricas['associado_abac preenchido']++;
                }

                if (($client->contato_name_admin === null || $client->contato_name_admin === '')
                    && $origem->contato_name_admin !== null && $origem->contato_name_admin !== '') {
                    $update['contato_name_admin'] = $origem->contato_name_admin;
                    $metricas['contato_name_admin preenchido']++;
                }

                if (! $perdaPendente && $this->notesSoTemRotulosLegado($client->notes)) {
                    $update['notes'] = null;
                    $metricas['notes limpo']++;
                }

                if ($update === []) {
                    $metricas['já estavam corretos']++;

                    continue;
                }

                if (! $dryRun) {
                    DB::table('clients')->where('id', $client->id)->update($update);
                }
            }
        });

        $this->newLine();
        $this->table(
            ['Métrica', 'Valor'],
            collect($metricas)->map(fn ($v, $k) => [$k, $v])->values()->all(),
        );

        if ($regionaisNaoCasadas !== []) {
            $this->newLine();
            $this->warn('Regionais do legado sem correspondência em `regionais` (rode ListasDominioSeeder):');

            foreach ($regionaisNaoCasadas as $nome => $qtd) {
                $this->line(sprintf('  %-40s %d cliente(s)', $nome, $qtd));
            }
        }

        return self::SUCCESS;
    }

    /**
     * Índice nome-normalizado => id das regionais cadastradas.
     *
     * @return array<string, int>
     */
    private function mapaRegionais(): array
    {
        return Regional::query()
            ->get(['id', 'nome'])
            ->mapWithKeys(fn ($r) => [$this->normalizarNome($r->nome) => $r->id])
            ->all();
    }

    /**
     * Clientes do legado indexados pelos dígitos do CNPJ/CPF.
     *
     * @return array<string, object>
     */
    private function carregarLegado(): array
    {
        try {
            $linhas = DB::connection('abac_admin')
                ->table('clients')
                ->select('cpf_cnpj', 'regional', 'associado', 'contato_name_admin')
                ->get();
        } catch (\Throwable $e) {
            $this->error('Falha ao ler o banco legado: '.$e->getMessage());

            return [];
        }

        $indexado = [];

        foreach ($linhas as $linha) {
            $chave = Normalizer::digits($linha->cpf_cnpj);

            if ($chave !== '') {
                $indexado[$chave] = $linha;
            }
        }

        return $indexado;
    }

    /**
     * Remove acentos, hífens e caixa para casar 'CENTRO OESTE' com 'Centro-Oeste'.
     */
    private function normalizarNome(?string $valor): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT', (string) $valor) ?: (string) $valor;

        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($semAcento)) ?? '';
    }

    /** 'S' => true, 'N' => false, qualquer outra coisa => não mexer. */
    private function interpretarAssociado(?string $valor): ?bool
    {
        return match (mb_strtoupper(trim((string) $valor))) {
            'S' => true,
            'N' => false,
            default => null,
        };
    }

    /**
     * Verdadeiro quando todas as linhas do `notes` são rótulos gerados pela
     * importação anterior — ou seja, não há texto do usuário a preservar.
     */
    private function notesSoTemRotulosLegado(?string $notes): bool
    {
        if ($notes === null || trim($notes) === '') {
            return false;
        }

        foreach (preg_split('/\R/', $notes) ?: [] as $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                continue;
            }

            $rotulo = trim(explode(':', $linha, 2)[0]);

            if (! in_array($rotulo, self::ROTULOS_LEGADO, true)) {
                return false;
            }
        }

        return true;
    }
}
