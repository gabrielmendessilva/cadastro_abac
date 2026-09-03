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

    /** Clientes desativados por estarem no RM com o cadastro fora de ordem (STATUS/OCORRENCIA != 'OK'). */
    public int $clientsDesativados = 0;

    public int $enderecosCriados = 0;

    /** Endereços criados em cliente que já existia e estava sem nenhum. */
    public int $backfillEnderecos = 0;

    public int $contatosCriados = 0;

    public int $contatosPuladosEmail = 0;

    public int $contatosPuladosNome = 0;

    public int $contatosPuladosSemChave = 0;

    public int $centrosCustoCriados = 0;

    public int $backfillCentroCusto = 0;

    public int $redesSociaisCriadas = 0;

    public int $sitesInvalidos = 0;

    /**
     * Colunas de clients preenchidas a partir dos campos opcionais da FCFO em
     * clientes que já existiam (as vazias — nada é sobrescrito).
     *
     * @var array<string,int>
     */
    public array $backfillCampos = [
        'num_filiacao_abac' => 0,
        'dt_filiacao_abac' => 0,
        'num_filiacao_sinac' => 0,
        'dt_filiacao_sinac' => 0,
        'dt_abertura_empresa' => 0,
        'categoria' => 0,
        'situacao_abac' => 0,
        'ocorrencia_abac' => 0,
    ];

    /**
     * Idem para client_contatos: colunas completadas em contato que já existia.
     *
     * @var array<string,int>
     */
    public array $backfillContato = [
        'funcao' => 0,
        'dt_nascimento' => 0,
        'aniversario' => 0,
        'celular' => 0,
        'departamento' => 0,
        'outro_departamento' => 0,
        'representante_legal' => 0,
        'comite' => 0,
    ];

    public int $comitesCriados = 0;

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
            ['Clientes desativados (cadastro fora de ordem no RM)', $this->clientsDesativados],
            ['Endereços criados (clientes novos)', $this->enderecosCriados],
            ['Endereços criados (clientes existentes)', $this->backfillEnderecos],
            ['Contatos criados', $this->contatosCriados],
            ['Contatos pulados (e-mail já existia)', $this->contatosPuladosEmail],
            ['Contatos pulados (nome já existia)', $this->contatosPuladosNome],
            ['Contatos pulados (sem e-mail e sem nome)', $this->contatosPuladosSemChave],
            ['Centros de custo criados (clientes novos)', $this->centrosCustoCriados],
            ['Centros de custo criados (clientes existentes)', $this->backfillCentroCusto],
            ['Sites criados (redes sociais)', $this->redesSociaisCriadas],
            ['Sites ignorados (valor não é endereço)', $this->sitesInvalidos],
            ['Nº filiação ABAC preenchido (existentes)', $this->backfillCampos['num_filiacao_abac']],
            ['Data de filiação ABAC preenchida (existentes)', $this->backfillCampos['dt_filiacao_abac']],
            ['Nº filiação SINAC preenchido (existentes)', $this->backfillCampos['num_filiacao_sinac']],
            ['Data de filiação SINAC preenchida (existentes)', $this->backfillCampos['dt_filiacao_sinac']],
            ['Data de abertura preenchida (existentes)', $this->backfillCampos['dt_abertura_empresa']],
            ['Categoria preenchida (existentes)', $this->backfillCampos['categoria']],
            ['Situação ABAC preenchida (existentes)', $this->backfillCampos['situacao_abac']],
            ['Ocorrência ABAC preenchida (existentes)', $this->backfillCampos['ocorrencia_abac']],
            ['Vínculos de comitê criados', $this->comitesCriados],
            ['Contatos: função preenchida (existentes)', $this->backfillContato['funcao']],
            ['Contatos: nascimento preenchido (existentes)', $this->backfillContato['dt_nascimento']],
            ['Contatos: aniversário preenchido (existentes)', $this->backfillContato['aniversario']],
            ['Contatos: celular preenchido (existentes)', $this->backfillContato['celular']],
            ['Contatos: departamento preenchido (existentes)', $this->backfillContato['departamento']],
            ['Contatos: outros departamentos (existentes)', $this->backfillContato['outro_departamento']],
            ['Contatos: representante legal (existentes)', $this->backfillContato['representante_legal']],
            ['Contatos: marcados em comitê (existentes)', $this->backfillContato['comite']],
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
            'clients_desativados' => $this->clientsDesativados,
            'enderecos_criados' => $this->enderecosCriados,
            'enderecos_criados_existentes' => $this->backfillEnderecos,
            'contatos_criados' => $this->contatosCriados,
            'contatos_pulados_email' => $this->contatosPuladosEmail,
            'contatos_pulados_nome' => $this->contatosPuladosNome,
            'contatos_pulados_sem_chave' => $this->contatosPuladosSemChave,
            'centros_custo_criados' => $this->centrosCustoCriados,
            'centros_custo_criados_existentes' => $this->backfillCentroCusto,
            'redes_sociais_criadas' => $this->redesSociaisCriadas,
            'sites_invalidos' => $this->sitesInvalidos,
            'backfill_campos' => $this->backfillCampos,
            'backfill_contato' => $this->backfillContato,
            'comites_criados' => $this->comitesCriados,
            'emails_excedentes' => $this->emailsExcedentes,
            'erros' => $this->erros,
            'warnings' => $this->warnings,
            'warnings_suprimidos' => $this->warningsSuprimidos,
        ];
    }
}
