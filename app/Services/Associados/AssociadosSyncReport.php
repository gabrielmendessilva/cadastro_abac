<?php

namespace App\Services\Associados;

/**
 * Contadores e amostras de warnings de uma execução do associados:sync.
 */
final class AssociadosSyncReport
{
    public int $cnpjsLidos = 0;

    public int $cnpjsInvalidos = 0;

    public int $cnpjsAgrupados = 0;

    public int $clientsCriados = 0;

    public int $clientsAtualizados = 0;

    public int $clientsJaSincronizados = 0;

    public int $contatosCriados = 0;

    public int $contatosAtualizados = 0;

    public int $contatosSemMudanca = 0;

    public int $enderecosCriados = 0;

    public int $enderecosAtualizados = 0;

    public int $enderecosSemMudanca = 0;

    public int $usuariosSemEmail = 0;

    public int $usuariosOrfaos = 0;

    public int $conflitosDeMeta = 0;

    public int $erros = 0;

    /** @var array<string,int> meta_key sem de-para (fora do meta_ignore) => nº de linhas no WP */
    public array $metasNaoMapeadas = [];

    /** @var list<string> entradas do meta_map cujo destino não existe no banco vivo */
    public array $colunasDescartadas = [];

    /** @var list<array{message:string,context:array<string,mixed>}> */
    public array $warnings = [];

    public int $warningsSuprimidos = 0;

    public function __construct(private readonly int $maxWarningSamples = 200) {}

    /**
     * Snapshot dos contadores, para restaurar quando a transação de um CNPJ sofre
     * rollback — senão o relatório afirmaria escritas que não existem no banco.
     *
     * @return array<string,int>
     */
    public function counters(): array
    {
        $out = [];

        foreach (get_object_vars($this) as $prop => $value) {
            if (is_int($value) && $prop !== 'maxWarningSamples') {
                $out[$prop] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string,int> $counters
     */
    public function restoreCounters(array $counters): void
    {
        foreach ($counters as $prop => $value) {
            $this->{$prop} = $value;
        }
    }

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
            ['CNPJs lidos no WordPress (meta_values distintos)', $this->cnpjsLidos],
            ['CNPJs inválidos (pulados)', $this->cnpjsInvalidos],
            ['CNPJs com variantes fundidas (formatado + cru)', $this->cnpjsAgrupados],
            ['Clientes criados', $this->clientsCriados],
            ['Clientes atualizados', $this->clientsAtualizados],
            ['Clientes já sincronizados (sem mudança)', $this->clientsJaSincronizados],
            ['Contatos criados', $this->contatosCriados],
            ['Contatos atualizados', $this->contatosAtualizados],
            ['Contatos sem mudança', $this->contatosSemMudanca],
            ['Endereços criados', $this->enderecosCriados],
            ['Endereços atualizados', $this->enderecosAtualizados],
            ['Endereços sem mudança', $this->enderecosSemMudanca],
            ['Usuários WP sem e-mail (pulados)', $this->usuariosSemEmail],
            ['Usuários WP órfãos (usermeta sem wp_users)', $this->usuariosOrfaos],
            ['Conflitos de meta entre usuários do mesmo CNPJ', $this->conflitosDeMeta],
            ['Meta_keys sem de-para (fora do meta_ignore)', count($this->metasNaoMapeadas)],
            ['Entradas do meta_map descartadas (coluna inexistente)', count($this->colunasDescartadas)],
            ['Erros (CNPJs pulados por falha)', $this->erros],
            ['Warnings registrados', count($this->warnings) + $this->warningsSuprimidos],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'cnpjs_lidos' => $this->cnpjsLidos,
            'cnpjs_invalidos' => $this->cnpjsInvalidos,
            'cnpjs_agrupados' => $this->cnpjsAgrupados,
            'clients_criados' => $this->clientsCriados,
            'clients_atualizados' => $this->clientsAtualizados,
            'clients_ja_sincronizados' => $this->clientsJaSincronizados,
            'contatos_criados' => $this->contatosCriados,
            'contatos_atualizados' => $this->contatosAtualizados,
            'contatos_sem_mudanca' => $this->contatosSemMudanca,
            'enderecos_criados' => $this->enderecosCriados,
            'enderecos_atualizados' => $this->enderecosAtualizados,
            'enderecos_sem_mudanca' => $this->enderecosSemMudanca,
            'usuarios_sem_email' => $this->usuariosSemEmail,
            'usuarios_orfaos' => $this->usuariosOrfaos,
            'conflitos_de_meta' => $this->conflitosDeMeta,
            'metas_nao_mapeadas' => $this->metasNaoMapeadas,
            'colunas_descartadas' => $this->colunasDescartadas,
            'erros' => $this->erros,
            'warnings' => $this->warnings,
            'warnings_suprimidos' => $this->warningsSuprimidos,
        ];
    }
}
