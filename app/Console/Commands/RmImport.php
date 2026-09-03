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
        {--cnpj=* : Restringe a importação aos CNPJ/CPF informados (com ou sem máscara)}
        {--chunk= : Tamanho do chunk de leitura (default: config rm.import.chunk)}
        {--no-backfill : Não completar clients já existentes (centro de custo, site e campos opcionais do RM)}
        {--no-desativar : Não desativar quem está no RM com STATUS/OCORRENCIA diferente de OK}
        {--somente-enderecos : Preenche SÓ endereço, e só de cliente que já existe aqui sem nenhum. Não cria cliente nem toca em contato, centro de custo, site, campos opcionais ou status}';

    protected $description = 'Importa clientes/fornecedores, contatos e centros de custo do TOTVS RM (SQL Server)';

    public function handle(RmImportService $service, RmReaderInterface $reader): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $somenteEnderecos = (bool) $this->option('somente-enderecos');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $coligada = $this->option('coligada') !== null ? (int) $this->option('coligada') : null;
        $documentos = array_values(array_filter((array) $this->option('cnpj')));
        $chunk = $this->option('chunk') !== null
            ? max(1, (int) $this->option('chunk'))
            : max(1, (int) config('rm.import.chunk', 300));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        if ($somenteEnderecos) {
            $this->warn('SOMENTE ENDEREÇOS: a única escrita será em client_enderecos, e só para cliente que já existe aqui e está sem nenhum endereço.');
        }

        try {
            $total = $reader->countFcfo($coligada, $documentos);

            if ($documentos !== [] && $total === 0) {
                $this->error('Nenhum dos documentos informados existe na FCFO do RM: '.implode(', ', $documentos));

                return self::FAILURE;
            }
            $planned = $limit !== null ? min($limit, $total) : $total;

            $this->info(sprintf(
                'FCFO: %d registro(s) no RM%s%s — processando %d.',
                $total,
                $coligada !== null ? " (coligada {$coligada})" : '',
                $documentos !== [] ? ' (restrito a '.count($documentos).' documento(s))' : '',
                $planned,
            ));

            $bar = $this->output->createProgressBar($planned);
            $bar->start();

            $report = $service->run(new RmImportOptions(
                dryRun: $dryRun,
                limit: $limit,
                coligada: $coligada,
                chunkSize: $chunk,
                documentos: $documentos,
                backfill: ! $this->option('no-backfill') && (bool) config('rm.import.backfill', true),
                somenteEnderecos: $somenteEnderecos,
                desativarForaDeOrdem: ! $this->option('no-desativar')
                    && (bool) config('rm.import.desativar_fora_de_ordem', true),
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
