<?php

namespace App\Console\Commands;

use App\Services\AbacAdmin\AbacAdminImportOptions;
use App\Services\AbacAdmin\AbacAdminImportService;
use App\Services\AbacAdmin\Exceptions\AbacAdminImportException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Migra clients e client_contatos do banco legado abac_admin (conexão 'abac_admin')
 * para o banco default do app, remapeando client_id e pulando contatos órfãos.
 *
 * Idempotente: dedup de cliente por CPF/CNPJ e de contato por e-mail/nome por cliente.
 */
class AbacAdminImport extends Command
{
    protected $signature = 'abac-admin:import
        {--dry-run : Não grava nada; só relata o que seria feito}
        {--limit= : Migra no máximo N clientes (contatos dos demais ficam de fora)}
        {--chunk=500 : Tamanho do chunk de leitura}';

    protected $description = 'Migra clients e client_contatos do banco legado abac_admin para o banco atual';

    public function handle(): int
    {
        $service = new AbacAdminImportService(logger: Log::channel('abac_admin'));

        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $chunk = max(1, (int) $this->option('chunk'));

        $this->info(sprintf(
            'Origem: conexão [abac_admin] %s — Destino: conexão default [%s] %s',
            (string) config('database.connections.abac_admin.database'),
            (string) config('database.default'),
            (string) config('database.connections.' . config('database.default') . '.database'),
        ));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        try {
            $probe = new AbacAdminImportOptions(dryRun: $dryRun, limit: $limit, chunkSize: $chunk);
            $bar = $this->output->createProgressBar($service->countSource($probe));
            $bar->start();

            $report = $service->run(new AbacAdminImportOptions(
                dryRun: $dryRun,
                limit: $limit,
                chunkSize: $chunk,
                onChunk: fn (int $processed) => $bar->advance($processed),
            ));

            $bar->finish();
            $this->newLine(2);
        } catch (AbacAdminImportException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Métrica', 'Qtd'], $report->toRows());

        if ($report->colunasDescartadas !== []) {
            $this->newLine();
            $this->warn('Colunas da origem descartadas (sem destino): ' . implode(', ', $report->colunasDescartadas));
        }

        if ($report->warnings !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d warning(s)%s — detalhes em storage/logs/abac_admin-*.log. Primeiros:',
                count($report->warnings) + $report->warningsSuprimidos,
                $report->warningsSuprimidos > 0 ? " ({$report->warningsSuprimidos} suprimidos do relatório)" : '',
            ));

            foreach (array_slice($report->warnings, 0, 10) as $warning) {
                $this->line('  - ' . $warning['message'] . ' ' . json_encode($warning['context'], JSON_UNESCAPED_UNICODE));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY-RUN concluído: NENHUMA escrita foi feita.');
        }

        return self::SUCCESS;
    }
}
