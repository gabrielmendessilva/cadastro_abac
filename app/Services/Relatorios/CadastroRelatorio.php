<?php

namespace App\Services\Relatorios;

use App\Models\Client;
use App\Models\ClientEndereco;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Relatório de cadastro: as colunas escolhidas pelo usuário e as linhas já
 * achatadas (cliente + endereço principal + regional). Tela, Excel e PDF só
 * desenham o que sai daqui.
 */
final class CadastroRelatorio
{
    public const TITULO = 'Relatório de Cadastro';

    /**
     * Colunas disponíveis — o "Mostrar" da grade do Access.
     *
     * - padrao: vem marcada quando o usuário não escolheu nada;
     * - tipo: 'texto' força string no Excel (CNPJ, telefone e CEP perdem zero
     *   à esquerda), 'data' grava data de verdade (ordenável);
     * - centro: centraliza a célula (campos curtos tipo Sim/Não).
     *
     * @var array<string,array{titulo:string,largura:int,padrao?:bool,tipo?:string,centro?:bool}>
     */
    public const COLUNAS = [
        'razao_social' => ['titulo' => 'Razão social', 'largura' => 42, 'padrao' => true],
        'nome_fantasia' => ['titulo' => 'Nome fantasia', 'largura' => 28, 'padrao' => true],
        'documento' => ['titulo' => 'CNPJ/CPF', 'largura' => 20, 'padrao' => true, 'tipo' => 'texto'],
        'categoria' => ['titulo' => 'Categoria', 'largura' => 22, 'padrao' => true],
        'associado_abac' => ['titulo' => 'Associado ABAC', 'largura' => 15, 'padrao' => true, 'centro' => true],
        'associado_sinac' => ['titulo' => 'Associado SINAC', 'largura' => 16, 'centro' => true],
        'status' => ['titulo' => 'Status', 'largura' => 10, 'padrao' => true, 'centro' => true],
        'regional' => ['titulo' => 'Regional', 'largura' => 22, 'padrao' => true],
        'email' => ['titulo' => 'E-mail', 'largura' => 32, 'padrao' => true],
        'telefone' => ['titulo' => 'Telefone', 'largura' => 16, 'padrao' => true, 'tipo' => 'texto'],
        'celular' => ['titulo' => 'Celular', 'largura' => 16, 'tipo' => 'texto'],
        'endereco' => ['titulo' => 'Endereço', 'largura' => 40],
        'bairro' => ['titulo' => 'Bairro', 'largura' => 20],
        'cidade' => ['titulo' => 'Cidade', 'largura' => 22, 'padrao' => true],
        'uf' => ['titulo' => 'UF', 'largura' => 6, 'padrao' => true, 'centro' => true],
        'cep' => ['titulo' => 'CEP', 'largura' => 11, 'tipo' => 'texto'],
        'num_filiacao_abac' => ['titulo' => 'Nº filiação ABAC', 'largura' => 16, 'tipo' => 'texto'],
        'dt_filiacao_abac' => ['titulo' => 'Filiação ABAC', 'largura' => 14, 'tipo' => 'data', 'centro' => true],
    ];

    /** Mesma prioridade de endereço da lista de clientes. */
    private const PRIORIDADE_ENDERECO = ['principal', 'pagamento', 'entrega'];

    public readonly string $geradoEm;

    public function __construct(
        public readonly CadastroFiltro $filtro,
        public readonly string $geradoPor,
    ) {
        $this->geradoEm = now()->format('d/m/Y H:i');
    }

    /** @return list<string> */
    public static function colunasPadrao(): array
    {
        return array_keys(array_filter(self::COLUNAS, fn ($coluna) => $coluna['padrao'] ?? false));
    }

    /** @return array<string,array{titulo:string,largura:int,padrao?:bool,tipo?:string,centro?:bool}> */
    public function colunas(): array
    {
        // Ordem fixa do relatório, não a ordem em que as caixas foram marcadas.
        return array_intersect_key(self::COLUNAS, array_flip($this->filtro->colunas));
    }

    /** @return Collection<int,array<string,mixed>> */
    public function linhas(): Collection
    {
        return $this->comRelacoes($this->filtro->query())
            ->get()
            ->map(fn (Client $client) => $this->linha($client));
    }

    /** Prévia da tela: mesma consulta, paginada. */
    public function pagina(int $porPagina = 25): LengthAwarePaginator
    {
        $pagina = $this->comRelacoes($this->filtro->query())->paginate($porPagina)->withQueryString();
        $pagina->setCollection($pagina->getCollection()->map(fn (Client $client) => $this->linha($client)));

        return $pagina;
    }

    /** Filtros em uma linha só, para o subtítulo da planilha e do PDF. */
    public function subtitulo(): string
    {
        $partes = [];
        foreach ($this->filtro->descricao() as $rotulo => $valor) {
            $partes[] = "{$rotulo}: {$valor}";
        }

        return implode('  •  ', $partes);
    }

    public function nomeDoArquivo(string $extensao): string
    {
        return 'relatorio-cadastro-'.now()->format('Y-m-d-His').'.'.$extensao;
    }

    /** Valor de célula como texto, para a tela e o PDF. */
    public static function texto(mixed $valor): string
    {
        if ($valor instanceof CarbonInterface) {
            return $valor->format('d/m/Y');
        }

        return (string) ($valor ?? '');
    }

    public static function logo(): string
    {
        return resource_path('images/abac-logo.jpg');
    }

    /**
     * Coluna do endereço preferido do cliente, como subquery — para ordenar
     * por UF/cidade sem join (o join duplicaria o cliente com vários endereços).
     * CASE em vez de FIELD() para rodar também no SQLite dos testes.
     */
    public static function subqueryEndereco(string $coluna): Builder
    {
        return ClientEndereco::query()
            ->select($coluna)
            ->whereColumn('client_enderecos.client_id', 'clients.id')
            ->orderByRaw("CASE tipo WHEN 'principal' THEN 0 WHEN 'pagamento' THEN 1 WHEN 'entrega' THEN 2 ELSE 3 END")
            ->limit(1);
    }

    private function comRelacoes(Builder $query): Builder
    {
        return $query->with(['enderecos', 'regional']);
    }

    /** @return array<string,mixed> */
    private function linha(Client $client): array
    {
        $ordem = array_flip(self::PRIORIDADE_ENDERECO);
        $endereco = $client->enderecos->sortBy(fn ($e) => $ordem[$e->tipo] ?? PHP_INT_MAX)->first();

        $logradouro = implode(', ', array_filter([$endereco?->rua, $endereco?->numero]));
        if ($endereco?->complemento) {
            $logradouro = trim($logradouro.' - '.$endereco->complemento, ' -');
        }

        return [
            'razao_social' => $client->name ?: $client->fantasy_name,
            'nome_fantasia' => $client->fantasy_name,
            'documento' => $client->document ?: $client->cpf,
            'categoria' => $client->categoria,
            'associado_abac' => $client->associado_abac ? 'Sim' : 'Não',
            'associado_sinac' => $client->associado_sinac ? 'Sim' : 'Não',
            'status' => $client->status ? 'Ativo' : 'Inativo',
            'regional' => $client->regional?->nome,
            'email' => $client->email,
            'telefone' => $client->phone,
            'celular' => $client->mobile,
            'endereco' => $logradouro !== '' ? $logradouro : null,
            'bairro' => $endereco?->bairro,
            'cidade' => $endereco?->municipio,
            'uf' => $endereco?->estado,
            'cep' => $endereco?->cep,
            'num_filiacao_abac' => $client->num_filiacao_abac,
            'dt_filiacao_abac' => $client->dt_filiacao_abac,
        ];
    }
}
