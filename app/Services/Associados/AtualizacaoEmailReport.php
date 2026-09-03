<?php

namespace App\Services\Associados;

/**
 * Contadores e amostra de mudanças de uma execução do
 * associados:atualizar-emails. É o relatório mostrado pelo comando e gravado
 * no canal de log 'associados'.
 */
final class AtualizacaoEmailReport
{
    /** Contas-empresa lidas no WordPress (usuário que carrega a razão social). */
    public int $empresasLidas = 0;

    /** Conta-empresa sem meta de CNPJ — não há como casar com o cliente. */
    public int $semCnpj = 0;

    /** Conta-empresa cujo e-mail de origem não é um e-mail válido. */
    public int $emailInvalido = 0;

    /** Mesmo CNPJ com mais de uma conta-empresa no WP (prevalece o menor user_id). */
    public int $cnpjDuplicado = 0;

    /** CNPJ do WP sem cliente correspondente no destino. */
    public int $semCliente = 0;

    /** Cliente cujo e-mail já era o de origem — nada a fazer. */
    public int $semMudanca = 0;

    /** Clientes que tiveram o e-mail sobrescrito. */
    public int $atualizados = 0;

    /**
     * Amostra de "de → para" para conferência e log.
     *
     * @var list<array{documento:string,de:string,para:string}>
     */
    public array $mudancas = [];

    public function __construct(private readonly int $maxSamples = 500) {}

    public function registraMudanca(string $documento, string $de, string $para): void
    {
        $this->atualizados++;

        if (count($this->mudancas) < $this->maxSamples) {
            $this->mudancas[] = ['documento' => $documento, 'de' => $de, 'para' => $para];
        }
    }

    /**
     * @return list<array{0:string,1:int}> pares [métrica, quantidade] para tabela
     */
    public function toRows(): array
    {
        return [
            ['Contas-empresa lidas no WP', $this->empresasLidas],
            ['Sem CNPJ na origem', $this->semCnpj],
            ['E-mail de origem inválido', $this->emailInvalido],
            ['CNPJ duplicado no WP (menor user_id vence)', $this->cnpjDuplicado],
            ['CNPJ sem cliente no destino', $this->semCliente],
            ['E-mail já estava atualizado', $this->semMudanca],
            ['E-mails sobrescritos', $this->atualizados],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'empresas_lidas' => $this->empresasLidas,
            'sem_cnpj' => $this->semCnpj,
            'email_invalido' => $this->emailInvalido,
            'cnpj_duplicado' => $this->cnpjDuplicado,
            'sem_cliente' => $this->semCliente,
            'sem_mudanca' => $this->semMudanca,
            'atualizados' => $this->atualizados,
        ];
    }
}
