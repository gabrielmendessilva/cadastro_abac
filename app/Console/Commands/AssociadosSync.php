<?php

namespace App\Console\Commands;

use App\Services\Associados\AssociadosSyncOptions;
use App\Services\Associados\AssociadosSyncService;
use App\Services\Associados\Exceptions\AssociadosSyncException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza clients e client_contatos a partir do WordPress dos associados
 * (conexão config('associados.connection')).
 *
 * Chave do cliente é o CNPJ (dígitos normalizados); cliente existente é
 * atualizado (associado_abac + meta_map), inexistente é criado. Chave do
 * contato é client_id + e-mail. Idempotente.
 */
class AssociadosSync extends Command
{
    protected $signature = 'associados:sync
        {--dry-run : Não grava nada; só relata o que seria feito}
        {--limit= : Processa no máximo N CNPJs}
        {--chunk= : CNPJs por chunk (default: config associados.sync.chunk)}
        {--discover : Só lista as meta_keys dos usuários associados, com contagem — não grava nada}';

    protected $description = 'Sincroniza clientes e contatos do WordPress dos associados para o banco atual';

    public function handle(): int
    {
        $service = new AssociadosSyncService(logger: Log::channel('associados'));

        $this->info(sprintf(
            'Origem: conexão [%s] %s — Destino: conexão default [%s] %s',
            (string) config('associados.connection'),
            (string) config('database.connections.'.config('associados.connection').'.database'),
            (string) config('database.default'),
            (string) config('database.connections.'.config('database.default').'.database'),
        ));

        try {
            if ($this->option('discover')) {
                return $this->discover($service);
            }

            return $this->sync($service);
        } catch (AssociadosSyncException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function discover(AssociadosSyncService $service): int
    {
        $rows = $service->discover();

        if ($rows === []) {
            $this->warn('Nenhuma meta encontrada para os usuários associados.');

            return self::SUCCESS;
        }

        $this->table(
            ['meta_key', 'Linhas', 'Usuários', 'Status'],
            array_map(static fn (array $row): array => [
                $row['meta_key'],
                $row['linhas'],
                $row['usuarios'],
                $row['status'],
            ], $rows),
        );

        $this->line('Preencha meta_map/meta_ignore em config/associados.php com as chaves acima e rode o sync.');

        return self::SUCCESS;
    }

    private function sync(AssociadosSyncService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $chunk = $this->option('chunk') !== null
            ? max(1, (int) $this->option('chunk'))
            : max(1, (int) config('associados.sync.chunk', 200));
        $maxWarnings = max(1, (int) config('associados.sync.max_warning_samples', 200));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        if ((array) config('associados.meta_map', []) === []) {
            $this->warn('meta_map vazio em config/associados.php: só associado_abac será atualizado nos clientes. Rode --discover para mapear campos.');
        }

        $probe = new AssociadosSyncOptions(dryRun: $dryRun, limit: $limit, chunkSize: $chunk);
        $bar = $this->output->createProgressBar($service->countCnpjs($probe));
        $bar->start();

        $report = $service->run(new AssociadosSyncOptions(
            dryRun: $dryRun,
            limit: $limit,
            chunkSize: $chunk,
            maxWarningSamples: $maxWarnings,
            onChunk: fn (int $processed) => $bar->advance($processed),
        ));

        $bar->finish();
        $this->newLine(2);

        $this->table(['Métrica', 'Qtd'], $report->toRows());

        if ($report->colunasDescartadas !== []) {
            $this->newLine();
            $this->warn('Entradas do meta_map descartadas (coluna inexistente ou proibida): '.implode(', ', $report->colunasDescartadas));
        }

        if ($report->metasNaoMapeadas !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d meta_key(s) sem de-para — rode `associados:sync --discover` e preencha config/associados.php.',
                count($report->metasNaoMapeadas),
            ));
        }

        if ($report->warnings !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d warning(s)%s — detalhes em storage/logs/associados-*.log. Primeiros:',
                count($report->warnings) + $report->warningsSuprimidos,
                $report->warningsSuprimidos > 0 ? " ({$report->warningsSuprimidos} suprimidos do relatório)" : '',
            ));

            foreach (array_slice($report->warnings, 0, 10) as $warning) {
                $this->line('  - '.$warning['message'].' '.json_encode($warning['context'], JSON_UNESCAPED_UNICODE));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY-RUN concluído: NENHUMA escrita foi feita.');
        }

        // Falha total (todo CNPJ deu erro) não pode sair com exit 0 — cron/CI
        // precisam enxergar o problema.
        $sucessos = $report->clientsCriados + $report->clientsAtualizados + $report->clientsJaSincronizados;
        if ($report->erros > 0 && $sucessos === 0) {
            $this->newLine();
            $this->error('Nenhum CNPJ foi sincronizado com sucesso — veja os warnings acima.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
