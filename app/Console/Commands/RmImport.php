<?php

namespace App\Console\Commands;

use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\Exceptions\RmImportException;
use App\Services\Rm\RmImportOptions;
use App\Services\Rm\RmImportService;
use Illuminate\Console\Command;

/**
 * Importa clientes/fornecedores, contatos e centros de custo do TOTVS RM (SQL Server).
 *
 * Idempotente: pode ser re-executado sem duplicar (dedup de cliente por CNPJ e de
 * contato por e-mail/nome). Para agendar no futuro, basta registrar em routes/console.php:
 *   Schedule::command('rm:import')->dailyAt('05:00');
 */
class RmImport extends Command
{
    protected $signature = 'rm:import
        {--dry-run : Não grava nada; só relata o que seria feito}
        {--limit= : Processa no máximo N registros FCFO}
        {--coligada= : Restringe a uma coligada do RM}
        {--chunk= : Tamanho do chunk de leitura (default: config rm.import.chunk)}
        {--no-backfill : Não preencher centro_custo_id em clients já existentes}';

    protected $description = 'Importa clientes/fornecedores, contatos e centros de custo do TOTVS RM (SQL Server)';

    public function handle(RmImportService $service, RmReaderInterface $reader): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $coligada = $this->option('coligada') !== null ? (int) $this->option('coligada') : null;
        $chunk = $this->option('chunk') !== null
            ? max(1, (int) $this->option('chunk'))
            : max(1, (int) config('rm.import.chunk', 300));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        try {
            $total = $reader->countFcfo($coligada);
            $planned = $limit !== null ? min($limit, $total) : $total;

            $this->info(sprintf(
                'FCFO: %d registro(s) no RM%s — processando %d.',
                $total,
                $coligada !== null ? " (coligada {$coligada})" : '',
                $planned,
            ));

            $bar = $this->output->createProgressBar($planned);
            $bar->start();

            $report = $service->run(new RmImportOptions(
                dryRun: $dryRun,
                limit: $limit,
                coligada: $coligada,
                chunkSize: $chunk,
                backfill: ! $this->option('no-backfill') && (bool) config('rm.import.backfill', true),
                includeContatoCompl: (bool) config('rm.import.include_contato_compl', true),
                maxWarningSamples: (int) config('rm.import.max_warning_samples', 200),
                onChunk: fn (int $processed) => $bar->advance($processed),
            ));

            $bar->finish();
            $this->newLine(2);
        } catch (RmImportException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Métrica', 'Qtd'], $report->toRows());

        if ($report->warnings !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d warning(s)%s — detalhes em storage/logs/rm-*.log. Primeiros:',
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
