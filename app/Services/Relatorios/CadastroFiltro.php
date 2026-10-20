<?php

namespace App\Services\Relatorios;

use App\Models\Client;
use App\Models\Lista\Regional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros da tela de Relatórios > Cadastro, lidos da query string.
 *
 * É a versão no app da "Consulta de Administradoras por Regional Ajustáveis"
 * do Access: cada campo da grade de critérios virou um filtro. Filtros
 * diferentes se combinam com E; opções marcadas dentro de um mesmo filtro
 * (tipos, UFs) se combinam com OU.
 */
final class CadastroFiltro
{
    /**
     * Tipos de cadastro marcáveis. 'demais' é tudo que não é administradora nem
     * categoria especial — inclusive cadastro sem categoria.
     */
    public const TIPOS = [
        'administradoras' => 'Administradoras',
        'categorias_especiais' => 'Categorias especiais',
        'demais' => 'Não administradoras (demais categorias)',
    ];

    public const ORDENS = [
        'razao_social' => 'Razão social',
        'uf_cidade' => 'UF e cidade',
        'categoria' => 'Categoria',
        'recentes' => 'Cadastrados mais recentemente',
    ];

    /**
     * @param  list<string>  $tipos
     * @param  list<string>  $ufs
     * @param  list<string>  $colunas
     */
    private function __construct(
        public readonly array $tipos,
        public readonly ?string $categoria,
        public readonly ?string $associadoAbac,
        public readonly ?string $associadoSinac,
        public readonly ?string $status,
        public readonly array $ufs,
        public readonly ?string $cidade,
        public readonly ?int $regionalId,
        public readonly ?string $busca,
        public readonly string $ordem,
        public readonly array $colunas,
    ) {}

    /**
     * Sem `aplicado=1` na URL é quem acabou de abrir a tela: entra o padrão
     * (só cadastros ativos). Depois do primeiro envio do formulário, vale o que
     * estiver nele — inclusive "Todos".
     */
    public static function fromRequest(Request $request): self
    {
        $aplicado = $request->query('aplicado') === '1';

        $texto = function (string $campo) use ($request): ?string {
            $valor = $request->query($campo);

            return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
        };

        $lista = fn (string $campo): array => array_values(array_unique(array_filter(
            (array) $request->query($campo, []),
            fn ($valor) => is_string($valor) && $valor !== '',
        )));

        $simNao = fn (?string $valor): ?string => in_array($valor, ['1', '0'], true) ? $valor : null;

        $colunas = array_values(array_intersect($lista('colunas'), array_keys(CadastroRelatorio::COLUNAS)));

        return new self(
            tipos: array_values(array_intersect($lista('tipo'), array_keys(self::TIPOS))),
            categoria: $texto('categoria'),
            associadoAbac: $simNao($texto('associado_abac')),
            associadoSinac: $simNao($texto('associado_sinac')),
            status: $simNao($aplicado ? $texto('status') : '1'),
            ufs: array_map('mb_strtoupper', $lista('uf')),
            cidade: $texto('cidade'),
            regionalId: ctype_digit((string) $texto('regional')) ? (int) $texto('regional') : null,
            busca: $texto('busca'),
            ordem: array_key_exists((string) $texto('ordem'), self::ORDENS) ? $texto('ordem') : 'razao_social',
            colunas: $colunas !== [] ? $colunas : CadastroRelatorio::colunasPadrao(),
        );
    }

    /** Query com todos os filtros e a ordenação, sem eager loading nem paginação. */
    public function query(): Builder
    {
        $query = Client::query()
            ->when($this->tipos !== [] && count($this->tipos) < count(self::TIPOS), fn ($q) => $this->filtraTipos($q))
            ->when($this->categoria !== null, fn ($q) => $q->where('categoria', $this->categoria))
            ->when($this->associadoAbac !== null, fn ($q) => $q->where('associado_abac', $this->associadoAbac === '1'))
            ->when($this->associadoSinac !== null, fn ($q) => $q->where('associado_sinac', $this->associadoSinac === '1'))
            ->when($this->status !== null, fn ($q) => $q->where('status', $this->status === '1'))
            ->when($this->ufs !== [], fn ($q) => $q->whereHas('enderecos', fn ($e) => $e->whereIn('estado', $this->ufs)))
            ->when($this->cidade !== null, fn ($q) => $q->whereHas('enderecos', fn ($e) => $e->where('municipio', 'like', "%{$this->cidade}%")))
            ->when($this->regionalId !== null, fn ($q) => $q->where('regional_id', $this->regionalId))
            ->when($this->busca !== null, fn ($q) => $this->filtraBusca($q));

        return $this->ordena($query);
    }

    /**
     * Os filtros em uso, em português, para o cabeçalho do arquivo: quem abre o
     * relatório depois precisa saber que recorte da base está olhando.
     *
     * @return array<string,string> rótulo => valor
     */
    public function descricao(): array
    {
        $simNao = fn (?string $valor, string $sim, string $nao) => $valor === null ? null : ($valor === '1' ? $sim : $nao);

        return array_filter([
            'Tipo' => $this->tipos === [] ? 'Todos' : implode(', ', array_map(fn ($t) => self::TIPOS[$t], $this->tipos)),
            'Categoria' => $this->categoria,
            'Associado ABAC' => $simNao($this->associadoAbac, 'Sim', 'Não'),
            'Associado SINAC' => $simNao($this->associadoSinac, 'Sim', 'Não'),
            'Status' => $simNao($this->status, 'Ativo', 'Inativo') ?? 'Todos',
            'UF' => $this->ufs === [] ? null : implode(', ', $this->ufs),
            'Cidade' => $this->cidade,
            'Regional' => $this->regionalId !== null ? Regional::find($this->regionalId)?->nome : null,
            'Busca' => $this->busca,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    private function filtraTipos(Builder $query): void
    {
        $administradoras = Client::GRUPOS_CATEGORIA['administradoras']['categorias'];
        $especiais = Client::GRUPOS_CATEGORIA['socios_especiais']['categorias'];

        $query->where(function ($q) use ($administradoras, $especiais) {
            foreach ($this->tipos as $tipo) {
                match ($tipo) {
                    'administradoras' => $q->orWhereIn('categoria', $administradoras),
                    'categorias_especiais' => $q->orWhereIn('categoria', $especiais),
                    // NOT IN descarta NULL no SQL; cadastro sem categoria também é "demais".
                    'demais' => $q->orWhere(fn ($d) => $d
                        ->whereNotIn('categoria', [...$administradoras, ...$especiais])
                        ->orWhereNull('categoria')),
                };
            }
        });
    }

    /** Uma palavra por vez, todas obrigatórias — mesma regra da busca da lista de clientes. */
    private function filtraBusca(Builder $query): void
    {
        foreach (preg_split('/\s+/', $this->busca, -1, PREG_SPLIT_NO_EMPTY) as $termo) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$termo}%")
                ->orWhere('fantasy_name', 'like', "%{$termo}%")
                ->orWhere('nome_comercial', 'like', "%{$termo}%")
                ->orWhere('document', 'like', "%{$termo}%")
                ->orWhere('cpf', 'like', "%{$termo}%")
                ->orWhere('email', 'like', "%{$termo}%"));
        }
    }

    private function ordena(Builder $query): Builder
    {
        return match ($this->ordem) {
            'uf_cidade' => $query
                ->orderBy(CadastroRelatorio::subqueryEndereco('estado'))
                ->orderBy(CadastroRelatorio::subqueryEndereco('municipio'))
                ->orderBy('name'),
            'categoria' => $query->orderBy('categoria')->orderBy('name'),
            'recentes' => $query->latest(),
            default => $query->orderBy('name'),
        };
    }
}
