<?php

namespace App\Console\Commands;

use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\Exceptions\RmImportException;
use App\Services\Rm\RmImportOptions;
use App\Services\Rm\RmImportReport;
use App\Services\Rm\RmImportService;
use Illuminate\Console\Command;

/**
 * Sincroniza SÓ os contatos do TOTVS RM para empresas que já existem aqui.
 *
 * Recorte deliberado: cria o contato que o RM tem e o portal não, e completa as
 * colunas vazias de quem já existe. Não cria empresa e não encosta em endereço,
 * centro de custo, site, campos opcionais nem no status do cliente — para isso
 * existe o rm:import completo.
 *
 * Nada é sobrescrito: coluna preenchida aqui fica como está, mesmo divergindo do
 * RM. O contato apagado à mão no portal, porém, volta enquanto continuar no RM —
 * quem saiu da empresa precisa sair também da FCFOCONTATO.
 */
class RmContatos extends Command
{
    protected $signature = 'rm:contatos
        {--dry-run : Não grava nada; só relata o que seria feito}
        {--limit= : Processa no máximo N registros FCFO}
        {--coligada= : Restringe a uma coligada do RM}
        {--cnpj=* : Restringe aos CNPJ/CPF informados (com ou sem máscara)}
        {--chunk= : Tamanho do chunk de leitura (default: config rm.import.chunk)}';

    protected $description = 'Importa só os contatos do TOTVS RM: cria os que faltam e preenche campos vazios dos que já existem';

    public function handle(RmImportService $service, RmReaderInterface $reader): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $coligada = $this->option('coligada') !== null ? (int) $this->option('coligada') : null;
        $documentos = array_values(array_filter((array) $this->option('cnpj')));
        $chunk = $this->option('chunk') !== null
            ? max(1, (int) $this->option('chunk'))
            : max(1, (int) config('rm.import.chunk', 300));

        if ($dryRun) {
            $this->warn('DRY-RUN: nenhuma escrita será feita no banco.');
        }

        $this->warn('SOMENTE CONTATOS: a única tabela escrita é client_contatos (mais os vínculos de comitê). Empresa, endereço, centro de custo, site, campos opcionais e status ficam intocados.');

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
                backfill: true,
                somenteContatos: true,
                documentos: $documentos,
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

        $this->table(['Métrica', 'Qtd'], $this->rows($report));

        if ($report->warnings !== []) {
            $this->newLine();
            $this->warn(sprintf(
                '%d warning(s)%s — detalhes em storage/logs/rm-*.log. Primeiros:',
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

        return self::SUCCESS;
    }

    /**
     * Só as métricas que este modo pode mexer — o relatório completo do
     * rm:import traria duas dezenas de linhas garantidamente zeradas.
     *
     * @return list<array{0:string,1:int}>
     */
    private function rows(RmImportReport $report): array
    {
        $empresas = $report->fcfoLidos - $report->clientsPuladosInvalidos - $report->clientsPuladosAusentes;

        return [
            ['Registros FCFO lidos', $report->fcfoLidos],
            ['Empresas visitadas (já cadastradas aqui)', max(0, $empresas)],
            ['Empresas do RM ainda sem cadastro aqui', $report->clientsPuladosAusentes],
            ['Empresas puladas (documento inválido)', $report->clientsPuladosInvalidos],
            ['Contatos criados', $report->contatosCriados],
            ['Contatos já existentes (casados por e-mail)', $report->contatosPuladosEmail],
            ['Contatos já existentes (casados por nome)', $report->contatosPuladosNome],
            ['Contatos pulados (sem e-mail e sem nome)', $report->contatosPuladosSemChave],
            ['Campo preenchido: e-mail', $report->backfillContato['email']],
            ['Campo preenchido: 2º e-mail', $report->backfillContato['email_2']],
            ['Campo preenchido: telefone', $report->backfillContato['telefone']],
            ['Campo preenchido: ramal', $report->backfillContato['ramal']],
            ['Campo preenchido: celular', $report->backfillContato['celular']],
            ['Campo preenchido: função', $report->backfillContato['funcao']],
            ['Campo preenchido: nascimento', $report->backfillContato['dt_nascimento']],
            ['Campo preenchido: aniversário', $report->backfillContato['aniversario']],
            ['Campo preenchido: departamento', $report->backfillContato['departamento']],
            ['Campo preenchido: outros departamentos', $report->backfillContato['outro_departamento']],
            ['Campo preenchido: representante legal', $report->backfillContato['representante_legal']],
            ['Campo preenchido: marcação de comitê', $report->backfillContato['comite']],
            ['Vínculos de comitê criados', $report->comitesCriados],
            ['Erros (linhas puladas por falha)', $report->erros],
            ['Warnings registrados', count($report->warnings) + $report->warningsSuprimidos],
        ];
    }
}
