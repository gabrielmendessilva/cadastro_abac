<?php

namespace App\Services\Clientes;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros da lista de cadastro, lidos da query string.
 *
 * A tela (/clients) e os relatórios (Excel e PDF) partem daqui: o relatório
 * tem que sair com exatamente o que o usuário está vendo na lista, e duas
 * cópias da mesma regra acabam divergindo.
 */
final class ClientesFiltro
{
    /**
     * @param  list<string>  $tiposSelecionados
     */
    private function __construct(
        public readonly ?string $busca,
        public readonly ?string $filtroStatus,
        public readonly bool $mostraAssociadas,
        public readonly bool $mostraNaoAssociadas,
        public readonly array $tiposSelecionados,
        public readonly ?string $uf,
        public readonly ?string $cidade,
        public readonly ?string $ordenarPor,
        public readonly string $direcao,
    ) {}

    public static function fromRequest(Request $request): self
    {
        // Ordenação da lista. Só 'name' é ordenável pela tela; sem escolha, cai
        // no padrão de sempre (mais recentes primeiro).
        return new self(
            busca: $request->filled('search') ? trim((string) $request->string('search')) : null,
            filtroStatus: self::filtroComPadrao($request, 'status', '1'),
            mostraAssociadas: self::caixaMarcada($request, 'associadas', true),
            mostraNaoAssociadas: self::caixaMarcada($request, 'nao_associadas', false),
            tiposSelecionados: self::tiposSelecionados($request),
            uf: $request->filled('state') ? (string) $request->string('state') : null,
            cidade: $request->filled('city') ? (string) $request->string('city') : null,
            ordenarPor: $request->query('sort') === 'name' ? 'name' : null,
            direcao: $request->query('dir') === 'desc' ? 'desc' : 'asc',
        );
    }

    /**
     * true/false quando a lista exige um vínculo com a ABAC, null quando não restringe.
     * As duas marcadas — ou as duas vazias — cobrem a base inteira.
     */
    public function vinculoExigido(): ?bool
    {
        return $this->mostraAssociadas === $this->mostraNaoAssociadas ? null : $this->mostraAssociadas;
    }

    /** Query com todos os filtros e a ordenação aplicados, sem paginação nem eager loading. */
    public function query(): Builder
    {
        $vinculoExigido = $this->vinculoExigido();
        $categoriasDoFiltro = $this->categoriasDoFiltro();

        return Client::query()
            ->when($this->busca !== null, function ($query) {
                // Uma palavra por vez, todas obrigatórias: nome de pessoa ("tania regina")
                // aparece com sobrenome no meio, espaçamento irregular vindo do legado e
                // às vezes só na ficha de contato/sócio — busca por frase inteira não acha.
                $termos = preg_split('/\s+/', $this->busca, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($termos as $termo) {
                    $query->where(function ($subQuery) use ($termo) {
                        $subQuery->where('name', 'like', "%{$termo}%")
                            ->orWhere('fantasy_name', 'like', "%{$termo}%")
                            ->orWhere('nome_comercial', 'like', "%{$termo}%")
                            ->orWhere('outros_nomes', 'like', "%{$termo}%")
                            ->orWhere('responsavel_empresa', 'like', "%{$termo}%")
                            ->orWhere('presidente_atual', 'like', "%{$termo}%")
                            ->orWhere('document', 'like', "%{$termo}%")
                            ->orWhere('cpf', 'like', "%{$termo}%")
                            ->orWhere('email', 'like', "%{$termo}%")
                            ->orWhere('cod_omie', 'like', "%{$termo}%")
                            ->orWhereHas('contatos', fn ($q) => $q->where('nome', 'like', "%{$termo}%"))
                            ->orWhereHas('socios', fn ($q) => $q->where('nome', 'like', "%{$termo}%"));
                    });
                }
            })
            ->when($this->filtroStatus !== null, fn ($query) => $query->where('status', $this->filtroStatus === '1'))
            ->when($vinculoExigido !== null, fn ($query) => $query->where('associado_abac', $vinculoExigido))
            // Tipo de cadastro: os botões da tela antiga do Access virados em
            // checkbox. null = sem restrição (nada marcado, ou "Outras Empresas").
            ->when($categoriasDoFiltro !== null, fn ($query) => $query->whereIn('categoria', $categoriasDoFiltro))
            ->when($this->uf !== null, fn ($query) => $query->whereHas('enderecos', fn ($q) => $q->where('estado', $this->uf)))
            ->when($this->cidade !== null, fn ($query) => $query->whereHas('enderecos', fn ($q) => $q->where('municipio', 'like', '%'.$this->cidade.'%')))
            ->when(
                $this->ordenarPor === 'name',
                fn ($query) => $query->orderBy('name', $this->direcao),
                fn ($query) => $query->latest(),
            );
    }

    /**
     * Os filtros em uso, em português, para o cabeçalho do relatório: quem abre
     * o arquivo depois precisa saber que recorte da base está olhando.
     *
     * @return array<string,string> rótulo => valor
     */
    public function descricao(): array
    {
        $tipos = array_map(fn ($tipo) => Client::GRUPOS_CATEGORIA[$tipo]['rotulo'], $this->tiposSelecionados);
        $vinculo = $this->vinculoExigido();

        return array_filter([
            'Tipo de cadastro' => $tipos === [] ? 'Todos' : implode(', ', $tipos),
            'Vínculo ABAC' => $vinculo === null ? 'Associadas e não associadas' : ($vinculo ? 'Associadas' : 'Não associadas'),
            'Status' => $this->filtroStatus === null ? 'Todos' : ($this->filtroStatus === '1' ? 'Ativo' : 'Inativo'),
            'Busca' => $this->busca,
            'Cidade' => $this->cidade,
            'UF' => $this->uf !== null ? mb_strtoupper($this->uf) : null,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    /**
     * Valor de um filtro da lista, com o padrão da tela quando a URL não diz nada.
     *
     * Chave ausente é quem acabou de chegar em /clients (login, menu, link solto):
     * entra o padrão. Chave presente e vazia é o usuário tendo escolhido "todos"
     * no formulário — essa escolha vale, senão ele nunca sairia do padrão.
     *
     * Devolve null quando o filtro não deve entrar na query.
     */
    private static function filtroComPadrao(Request $request, string $campo, string $padrao): ?string
    {
        $valor = $request->has($campo) ? $request->query($campo) : $padrao;

        return is_string($valor) && $valor !== '' ? $valor : null;
    }

    /**
     * Estado de uma caixa de filtro que tem input hidden de par na tela.
     *
     * Checkbox desmarcada não é enviada pelo navegador, então sem o hidden o
     * controller receberia a mesma requisição em dois casos opostos: "desmarquei"
     * e "acabei de abrir a tela". Com o hidden, chave ausente é só a abertura —
     * e aí entra o padrão.
     */
    private static function caixaMarcada(Request $request, string $campo, bool $padrao): bool
    {
        return $request->has($campo) ? $request->query($campo) === '1' : $padrao;
    }

    /**
     * Grupos de tipo de cadastro marcados na tela, descartando o que não existe.
     *
     * @return list<string>
     */
    private static function tiposSelecionados(Request $request): array
    {
        $marcados = array_filter((array) $request->query('tipo', []), 'is_string');

        return array_values(array_intersect($marcados, array_keys(Client::GRUPOS_CATEGORIA)));
    }

    /**
     * Categorias que a lista deve exigir, ou null quando não há restrição.
     *
     * Nada marcado é a lista inteira. "Outras Empresas" também: ele é a base
     * toda por definição do negócio, então marcar ele junto com outro grupo
     * dissolve a restrição em vez de somar mais uma fatia.
     *
     * @return list<string>|null
     */
    private function categoriasDoFiltro(): ?array
    {
        if ($this->tiposSelecionados === []) {
            return null;
        }

        $categorias = [];

        foreach ($this->tiposSelecionados as $tipo) {
            $doGrupo = Client::GRUPOS_CATEGORIA[$tipo]['categorias'];

            if ($doGrupo === []) {
                return null;
            }

            $categorias = array_merge($categorias, $doGrupo);
        }

        return array_values(array_unique($categorias));
    }
}
