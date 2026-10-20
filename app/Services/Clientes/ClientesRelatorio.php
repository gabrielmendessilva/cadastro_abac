<?php

namespace App\Services\Clientes;

use App\Models\Client;
use Illuminate\Support\Collection;

/**
 * Dados do relatório de cadastro: as linhas já achatadas (cliente + endereço
 * principal) e o cabeçalho com os filtros usados. Excel e PDF só desenham.
 */
final class ClientesRelatorio
{
    public const TITULO = 'Relatório de Cadastro';

    /**
     * Colunas do relatório. 'pdf' => false deixa a coluna só no Excel: no A4
     * deitado não cabe o endereço inteiro sem espremer o resto.
     *
     * @var array<string,array{titulo:string,largura:int,pdf?:bool,texto?:bool}>
     */
    public const COLUNAS = [
        'razao_social' => ['titulo' => 'Razão social', 'largura' => 42],
        'nome_fantasia' => ['titulo' => 'Nome fantasia', 'largura' => 30],
        'documento' => ['titulo' => 'CNPJ/CPF', 'largura' => 20, 'texto' => true],
        'categoria' => ['titulo' => 'Categoria', 'largura' => 22],
        'associado_abac' => ['titulo' => 'Associado ABAC', 'largura' => 15],
        'status' => ['titulo' => 'Status', 'largura' => 10],
        'email' => ['titulo' => 'E-mail', 'largura' => 32],
        'telefone' => ['titulo' => 'Telefone', 'largura' => 16, 'texto' => true],
        'celular' => ['titulo' => 'Celular', 'largura' => 16, 'texto' => true, 'pdf' => false],
        'endereco' => ['titulo' => 'Endereço', 'largura' => 40, 'pdf' => false],
        'bairro' => ['titulo' => 'Bairro', 'largura' => 20, 'pdf' => false],
        'cidade' => ['titulo' => 'Cidade', 'largura' => 22],
        'uf' => ['titulo' => 'UF', 'largura' => 6],
        'cep' => ['titulo' => 'CEP', 'largura' => 11, 'texto' => true, 'pdf' => false],
    ];

    /** Mesma prioridade de endereço da lista: principal, depois pagamento, depois entrega. */
    private const PRIORIDADE_ENDERECO = ['principal', 'pagamento', 'entrega'];

    public readonly string $geradoEm;

    public function __construct(
        private readonly ClientesFiltro $filtro,
        public readonly string $geradoPor,
    ) {
        $this->geradoEm = now()->format('d/m/Y H:i');
    }

    /** @return Collection<int,array<string,string|null>> */
    public function linhas(): Collection
    {
        return $this->filtro->query()
            ->with('enderecos')
            ->get()
            ->map(fn (Client $client) => $this->linha($client));
    }

    /** @return array<string,string> rótulo => valor */
    public function filtros(): array
    {
        return $this->filtro->descricao();
    }

    /** Filtros em uma linha só, para subtítulo de planilha e PDF. */
    public function subtitulo(): string
    {
        $partes = [];

        foreach ($this->filtros() as $rotulo => $valor) {
            $partes[] = "{$rotulo}: {$valor}";
        }

        return implode('  •  ', $partes);
    }

    /** @return array<string,array{titulo:string,largura:int,pdf?:bool,texto?:bool}> */
    public function colunasDoPdf(): array
    {
        return array_filter(self::COLUNAS, fn ($coluna) => $coluna['pdf'] ?? true);
    }

    public function nomeDoArquivo(string $extensao): string
    {
        return 'relatorio-cadastro-'.now()->format('Y-m-d-His').'.'.$extensao;
    }

    /** Caminho da logo usada no cabeçalho dos dois formatos. */
    public static function logo(): string
    {
        return resource_path('images/abac-logo.jpg');
    }

    /** @return array<string,string|null> */
    private function linha(Client $client): array
    {
        $ordem = array_flip(self::PRIORIDADE_ENDERECO);
        $endereco = $client->enderecos->sortBy(fn ($e) => $ordem[$e->tipo] ?? PHP_INT_MAX)->first();

        $logradouro = trim(implode(', ', array_filter([$endereco?->rua, $endereco?->numero])));
        if ($endereco?->complemento) {
            $logradouro = trim($logradouro.' - '.$endereco->complemento, ' -');
        }

        return [
            'razao_social' => $client->name ?: $client->fantasy_name,
            'nome_fantasia' => $client->fantasy_name,
            'documento' => $client->document ?: $client->cpf,
            'categoria' => $client->categoria,
            'associado_abac' => $client->associado_abac ? 'Sim' : 'Não',
            'status' => $client->status ? 'Ativo' : 'Inativo',
            'email' => $client->email,
            'telefone' => $client->phone,
            'celular' => $client->mobile,
            'endereco' => $logradouro !== '' ? $logradouro : null,
            'bairro' => $endereco?->bairro,
            'cidade' => $endereco?->municipio,
            'uf' => $endereco?->estado,
            'cep' => $endereco?->cep,
        ];
    }
}
