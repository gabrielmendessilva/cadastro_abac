<?php

namespace App\Services\Omie;

use App\Services\Omie\Contracts\ContasReceberRepositoryInterface;
use App\Services\Omie\Contracts\OmieClientInterface;

/**
 * Repository de Contas a Receber na Omie.
 *
 * Mapeia o payload interno (nomes do domínio do abac_admin) para o payload
 * da API Omie. Os nomes bizarros (`codRm`, `tipo_receber` em `numero_documento`,
 * `desconto` virando `valor_pis`+`retem_pis`) foram preservados por paridade
 * com o comportamento anterior — revisar com o time fiscal antes de mudar.
 */
class ContasReceberRepository implements ContasReceberRepositoryInterface
{
    private const ENDPOINT = 'v1/financas/contareceber/';

    public function __construct(
        private readonly OmieClientInterface $client,
    ) {}

    public function create(array $data): array
    {
        return $this->client->request(self::ENDPOINT, 'IncluirContaReceber', $this->mapCreate($data));
    }

    public function findById(int $omieId): array
    {
        return $this->client->request(self::ENDPOINT, 'ConsultarContaReceber', [
            'codigo_lancamento_omie' => $omieId,
        ]);
    }

    public function update(array $data): array
    {
        $param = $this->mapCreate($data);

        // Passa também identificador Omie quando disponível (para AlterarContaReceber)
        if (isset($data['codigo_lancamento_omie'])) {
            $param['codigo_lancamento_omie'] = (int) $data['codigo_lancamento_omie'];
        }

        return $this->client->request(self::ENDPOINT, 'AlterarContaReceber', $param);
    }

    public function registerPayment(array $data): array
    {
        // Nome semanticamente correto: `data_recebimento`. Fallback para `vencimento`
        // (@deprecated) só para não quebrar consumidores da origem, que usavam o campo
        // errado para simbolizar a data efetiva de recebimento.
        $data_recebimento = $data['data_recebimento'] ?? $data['vencimento'];

        return $this->client->request(self::ENDPOINT, 'LancarRecebimento', [
            'codigo_lancamento'     => (int) $data['codigo_lancamento'],
            'codigo_conta_corrente' => (int) $data['cc_id'],
            'valor'                 => (float) $data['valor'],
            'data'                  => $data_recebimento,
        ]);
    }

    public function delete(int $chaveLancamento): array
    {
        return $this->client->request(self::ENDPOINT, 'ExcluirContaReceber', [
            'chave_lancamento' => $chaveLancamento,
        ]);
    }

    /**
     * Espelha os campos que o `ContasReceberRepository` da origem enviava.
     *
     * Alerta: `numero_documento_fiscal` e `numero_documento` recebem `tipo_receber`
     * (não é bug de copy-paste — a origem faz isso de propósito, provavelmente
     * um hack fiscal do abac). Preservado por paridade.
     *
     * Alerta: campo `desconto` vira `valor_pis`+`retem_pis='S'` — outro hack fiscal
     * específico do abac. Não é desconto genérico, é retenção de PIS.
     *
     * @param  array<string,mixed>  $d
     * @return array<string,mixed>
     */
    private function mapCreate(array $d): array
    {
        $param = [
            'codigo_lancamento_integracao'         => $d['codigo_lancamento_integracao'] ?? null,
            'codigo_cliente_fornecedor'            => $d['codRm'] ?? null,
            'data_vencimento'                      => $d['vencimento'] ?? null,
            'valor_documento'                      => isset($d['valor']) ? (float) $d['valor'] : null,
            'id_conta_corrente'                    => $d['cc_id'] ?? null,
            'codigo_cliente_fornecedor_integracao' => $d['user_id'] ?? null,
            'codigo_categoria'                     => $d['categoria'] ?? null,
            'numero_documento_fiscal'              => $d['tipo_receber'] ?? null,
            'numero_documento'                     => $d['tipo_receber'] ?? null,
            'codigo_projeto'                       => $d['projeto'] ?? null,
            'observacao'                           => $d['observacao'] ?? null,
            'cPedidoCliente'                       => $d['idCompra'] ?? null,
            'numero_parcela'                       => $d['numero_parcela'] ?? null,
            'numero_pedido'                        => $d['idCompra'] ?? null,
        ];

        // Hack fiscal preservado da origem.
        if (isset($d['desconto']) && $d['desconto'] !== null && $d['desconto'] !== '') {
            $param['valor_pis'] = (float) $d['desconto'];
            $param['retem_pis'] = 'S';
        }

        return $param;
    }
}
