<?php

namespace App\Console\Commands;

use App\Services\Associados\AtualizacaoEmailOptions;
use App\Services\Associados\AtualizacaoEmailService;
use App\Services\Associados\Exceptions\AssociadosSyncException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sobrescreve clients.email das empresas associadas com o e-mail da conta-empresa
 * no WordPress dos associados.
 *
 * Fica separado do associados:sync de propósito: aquele só preenche coluna vazia
 * (o dado digitado à mão vence), enquanto aqui o e-mail do portal é a fonte da
 * verdade e SOBRESCREVE o que estiver no GED. Só a coluna email muda; nenhum
 * outro campo, contato ou endereço é tocado.
 */
class AssociadosAtualizarEmails extends Command
{
    protected $signature = 'associados:atualizar-emails
        {--dry-run : Não grava nada; só relata o que seria sobrescrito}';

    protected $description = 'Sobrescreve o e-mail das empresas associadas com o do banco de associados (WordPress)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Origem: conexão [%s] %s — Destino: conexão default [%s] %s',
            (string) config('associados.connection'),
            (string) config('database.connections.'.config('associados.connection').'.database'),
            (string) config('database.default'),
            (string) config('database.connections.'.config('database.default').'.database'),
        ));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        } else {
            $this->warn('Este comando SOBRESCREVE clients.email das empresas associadas — inclusive e-mail digitado à mão.');
        }

        $service = new AtualizacaoEmailService(logger: Log::channel('associados'));

        try {
            $report = $service->run(new AtualizacaoEmailOptions(dryRun: $dryRun));
        } catch (AssociadosSyncException $e) {
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Métrica', 'Qtd'], $report->toRows());

        if ($report->mudancas !== []) {
            $this->newLine();
            $this->line(sprintf('%s %d e-mail(s):', $dryRun ? 'Seriam trocados' : 'Trocados', $report->atualizados));

            $linhas = array_map(
                fn (array $m) => [$m['documento'], $m['de'], $m['para']],
                array_slice($report->mudancas, 0, 30),
            );
            $this->table(['CNPJ/CPF', 'De', 'Para'], $linhas);

            if ($report->atualizados > 30) {
                $this->line(sprintf('  … e mais %d — completo em storage/logs/associados-*.log.', $report->atualizados - 30));
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY-RUN concluído: NENHUMA escrita foi feita.');
        }

        return self::SUCCESS;
    }
}
