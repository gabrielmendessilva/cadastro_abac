<?php

namespace App\Services\AbacAdmin;

/**
 * Contadores e amostras de warnings de uma execução do abac-admin:import.
 */
final class AbacAdminImportReport
{
    public int $clientsLidos = 0;

    public int $clientsCriados = 0;

    public int $clientsPuladosExistentes = 0;

    public int $clientsSemDocumento = 0;

    public int $duplicadosNaOrigem = 0;

    public int $contatosLidos = 0;

    public int $contatosCriados = 0;

    public int $contatosPuladosEmail = 0;

    public int $contatosPuladosNome = 0;

    public int $contatosPuladosSemChave = 0;

    public int $contatosOrfaos = 0;

    public int $contatosSemClienteMigrado = 0;

    public int $erros = 0;

    /** @var list<string> colunas da origem sem coluna correspondente no destino */
    public array $colunasDescartadas = [];

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
     * @return list<array{0:string,1:int|string}>
     */
    public function toRows(): array
    {
        return [
            ['Clientes lidos na origem', $this->clientsLidos],
            ['Clientes criados', $this->clientsCriados],
            ['Clientes pulados (documento já existia)', $this->clientsPuladosExistentes],
            ['Clientes sem documento (dedup por nome)', $this->clientsSemDocumento],
            ['Documento duplicado dentro da origem', $this->duplicadosNaOrigem],
            ['Contatos lidos na origem', $this->contatosLidos],
            ['Contatos criados', $this->contatosCriados],
            ['Contatos pulados (e-mail já existia)', $this->contatosPuladosEmail],
            ['Contatos pulados (nome já existia)', $this->contatosPuladosNome],
            ['Contatos pulados (sem e-mail e sem nome)', $this->contatosPuladosSemChave],
            ['Contatos órfãos (client_id inexistente na origem)', $this->contatosOrfaos],
            ['Contatos com cliente fora desta execução', $this->contatosSemClienteMigrado],
            ['Colunas da origem descartadas', count($this->colunasDescartadas)],
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
            'clients_lidos' => $this->clientsLidos,
            'clients_criados' => $this->clientsCriados,
            'clients_pulados_existentes' => $this->clientsPuladosExistentes,
            'clients_sem_documento' => $this->clientsSemDocumento,
            'duplicados_na_origem' => $this->duplicadosNaOrigem,
            'contatos_lidos' => $this->contatosLidos,
            'contatos_criados' => $this->contatosCriados,
            'contatos_pulados_email' => $this->contatosPuladosEmail,
            'contatos_pulados_nome' => $this->contatosPuladosNome,
            'contatos_pulados_sem_chave' => $this->contatosPuladosSemChave,
            'contatos_orfaos' => $this->contatosOrfaos,
            'contatos_sem_cliente_migrado' => $this->contatosSemClienteMigrado,
            'colunas_descartadas' => $this->colunasDescartadas,
            'erros' => $this->erros,
            'warnings' => $this->warnings,
            'warnings_suprimidos' => $this->warningsSuprimidos,
        ];
    }
}
