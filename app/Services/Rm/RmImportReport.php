<?php

namespace App\Services\Rm;

/**
 * Contadores e amostras de warnings de uma execução do rm:import.
 * É o relatório exibido pelo comando e gravado no canal de log 'rm'.
 */
final class RmImportReport
{
    public int $fcfoLidos = 0;

    public int $clientsCriados = 0;

    public int $clientsPuladosExistentes = 0;

    public int $clientsPuladosInvalidos = 0;

    public int $duplicadosNoRm = 0;

    public int $enderecosCriados = 0;

    public int $contatosCriados = 0;

    public int $contatosPuladosEmail = 0;

    public int $contatosPuladosNome = 0;

    public int $contatosPuladosSemChave = 0;

    public int $centrosCustoCriados = 0;

    public int $backfillCentroCusto = 0;

    public int $emailsExcedentes = 0;

    public int $erros = 0;

    /** @var list<array{message:string,context:array<string,mixed>}> */
    public array $warnings = [];

    public int $warningsSuprimidos = 0;

    public function __construct(private readonly int $maxWarningSamples = 200) {}

    /**
     * @param array<string,mixed> $context
     */
    public function warn(string $message, array $context = []): void
    {
        if (count($this->warnings) < $this->maxWarningSamples) {
            $this->warnings[] = ['message' => $message, 'context' => $context];
        } else {
            $this->warningsSuprimidos++;
        }
    }

    /**
     * @return list<array{0:string,1:int}> pares [métrica, quantidade] para exibição em tabela
     */
    public function toRows(): array
    {
        return [
            ['Registros FCFO lidos', $this->fcfoLidos],
            ['Clientes criados', $this->clientsCriados],
            ['Clientes pulados (CNPJ já existia)', $this->clientsPuladosExistentes],
            ['Clientes pulados (documento inválido)', $this->clientsPuladosInvalidos],
            ['CNPJ duplicado dentro do RM', $this->duplicadosNoRm],
            ['Endereços criados', $this->enderecosCriados],
            ['Contatos criados', $this->contatosCriados],
            ['Contatos pulados (e-mail já existia)', $this->contatosPuladosEmail],
            ['Contatos pulados (nome já existia)', $this->contatosPuladosNome],
            ['Contatos pulados (sem e-mail e sem nome)', $this->contatosPuladosSemChave],
            ['Centros de custo criados (clientes novos)', $this->centrosCustoCriados],
            ['Centros de custo criados (clientes existentes)', $this->backfillCentroCusto],
            ['E-mails excedentes (sem coluna livre)', $this->emailsExcedentes],
            ['Erros (linhas puladas por falha)', $this->erros],
            ['Warnings registrados', count($this->warnings) + $this->warningsSuprimidos],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'fcfo_lidos' => $this->fcfoLidos,
            'clients_criados' => $this->clientsCriados,
            'clients_pulados_existentes' => $this->clientsPuladosExistentes,
            'clients_pulados_invalidos' => $this->clientsPuladosInvalidos,
            'duplicados_no_rm' => $this->duplicadosNoRm,
            'enderecos_criados' => $this->enderecosCriados,
            'contatos_criados' => $this->contatosCriados,
            'contatos_pulados_email' => $this->contatosPuladosEmail,
            'contatos_pulados_nome' => $this->contatosPuladosNome,
            'contatos_pulados_sem_chave' => $this->contatosPuladosSemChave,
            'centros_custo_criados' => $this->centrosCustoCriados,
            'centros_custo_criados_existentes' => $this->backfillCentroCusto,
            'emails_excedentes' => $this->emailsExcedentes,
            'erros' => $this->erros,
            'warnings' => $this->warnings,
            'warnings_suprimidos' => $this->warningsSuprimidos,
        ];
    }
}
