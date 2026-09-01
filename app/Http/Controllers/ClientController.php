<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Lista\Regional;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $filtroStatus = $this->filtroComPadrao($request, 'status', '1');
        $somenteAssociados = $this->somenteAssociados($request);
        $tiposSelecionados = $this->tiposSelecionados($request);
        $categoriasDoFiltro = $this->categoriasDoFiltro($tiposSelecionados);

        $clients = Client::query()
            ->withCount('documents')
            ->with(['enderecos' => fn ($q) => $q->orderByRaw("FIELD(tipo, 'principal','pagamento','entrega') ASC")->limit(1)])
            ->when($request->filled('search'), function ($query) use ($request) {
                // Uma palavra por vez, todas obrigatórias: nome de pessoa ("tania regina")
                // aparece com sobrenome no meio, espaçamento irregular vindo do legado e
                // às vezes só na ficha de contato/sócio — busca por frase inteira não acha.
                $termos = preg_split('/\s+/', trim((string) $request->string('search')), -1, PREG_SPLIT_NO_EMPTY);

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
            ->when($filtroStatus !== null, function ($query) use ($filtroStatus) {
                $query->where('status', $filtroStatus === '1');
            })
            ->when($somenteAssociados, fn ($query) => $query->where('associado_abac', true))
            // Tipo de cadastro: os botões da tela antiga do Access virados em
            // checkbox. null = sem restrição (nada marcado, ou "Outras Empresas").
            ->when($categoriasDoFiltro !== null, fn ($query) => $query->whereIn('categoria', $categoriasDoFiltro))
            ->when($request->filled('state'), fn($query) => $query->whereHas('enderecos', fn($q) => $q->where('estado', $request->string('state'))))
            ->when($request->filled('city'), fn($query) => $query->whereHas('enderecos', fn($q) => $q->where('municipio', 'like', '%' . $request->string('city') . '%')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', compact('clients', 'filtroStatus', 'somenteAssociados', 'tiposSelecionados'));
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
    private function filtroComPadrao(Request $request, string $campo, string $padrao): ?string
    {
        $valor = $request->has($campo) ? $request->query($campo) : $padrao;

        return is_string($valor) && $valor !== '' ? $valor : null;
    }

    /**
     * Se a lista deve mostrar só quem é associado à ABAC.
     *
     * Marcado é o estado de abertura da tela: sem a chave na URL (login, menu,
     * link solto) o filtro entra. Desmarcado, o formulário manda `associado=0`
     * pelo input hidden que acompanha a caixa — é só assim que dá para separar
     * "desmarquei" de "nem submeti", já que checkbox desmarcado não é enviado.
     *
     * Desmarcado não inverte o filtro: solta a lista inteira, associados e não
     * associados juntos.
     */
    private function somenteAssociados(Request $request): bool
    {
        return ! $request->has('associado') || $request->query('associado') === '1';
    }

    /**
     * Grupos de tipo de cadastro marcados na tela, descartando o que não existe.
     *
     * @return list<string>
     */
    private function tiposSelecionados(Request $request): array
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
     * @param list<string> $tipos
     * @return list<string>|null
     */
    private function categoriasDoFiltro(array $tipos): ?array
    {
        if ($tipos === []) {
            return null;
        }

        $categorias = [];

        foreach ($tipos as $tipo) {
            $doGrupo = Client::GRUPOS_CATEGORIA[$tipo]['categorias'];

            if ($doGrupo === []) {
                return null;
            }

            $categorias = array_merge($categorias, $doGrupo);
        }

        return array_values(array_unique($categorias));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('clients.create'), 403);

        return view('clients.create', ['regionais' => $this->regionais()]);
    }

    /** Lista de domínio que alimenta o select de regional nos formulários. */
    private function regionais()
    {
        return Regional::query()->where('ativo', true)->orderBy('nome')->get();
    }

    public function store(StoreClientRequest $request)
    {
        abort_unless(auth()->user()->can('clients.create'), 403);

        $client = Client::create($request->validated() + [
            'status' => $request->boolean('status', true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'geral'])
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function show(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $activeTab = $request->get('tab', 'geral');
        $activeSubtab = $request->get('subtab');

        $allowedTabs = [
            'geral', 'financeiro', 'juridico', 'secretaria',
            'cadastro', 'enderecos', 'contatos', 'opcionais',
            'tags', 'uso_interno',
        ];

        if (auth()->user()->can('documents.view')) {
            $allowedTabs[] = 'ged';
        }

        if (!in_array($activeTab, $allowedTabs, true)) {
            abort(403);
        }

        $gedCategory = $activeTab === 'ged' && in_array($activeSubtab, array_keys(\App\Models\Document::CATEGORIES), true)
            ? $activeSubtab
            : null;

        $documents = auth()->user()->can('documents.view')
            ? $client->documents()
                ->with('uploader')
                ->when($gedCategory, fn($q) => $q->where('category', $gedCategory))
                ->when($request->filled('document_search'), function ($query) use ($request) {
                    $search = $request->document_search;

                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('original_name', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(10, ['*'], 'documents_page')
                ->withQueryString()
            : collect();
    
        $addresses = $client->enderecos()
            ->when($request->filled('address_search'), function ($query) use ($request) {
                $search = $request->address_search;
    
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('cep', 'like', "%{$search}%")
                        ->orWhere('rua', 'like', "%{$search}%")
                        ->orWhere('numero', 'like', "%{$search}%")
                        ->orWhere('complemento', 'like', "%{$search}%")
                        ->orWhere('bairro', 'like', "%{$search}%")
                        ->orWhere('pais', 'like', "%{$search}%")
                        ->orWhere('estado', 'like', "%{$search}%")
                        ->orWhere('municipio', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'addresses_page')
            ->withQueryString();
    
        $contactSort = in_array($request->query('contact_sort'), ['nome', 'departamento'], true)
            ? $request->query('contact_sort')
            : null;
        $contactDir = $request->query('contact_dir') === 'desc' ? 'desc' : 'asc';

        $contacts = $client->contatos()
            ->when($request->filled('contact_search'), function ($query) use ($request) {
                $search = $request->contact_search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nome', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%")
                        ->orWhere('telefone_2', 'like', "%{$search}%")
                        ->orWhere('funcao', 'like', "%{$search}%")
                        ->orWhere('departamento', 'like', "%{$search}%");
                });
            })
            ->when(
                $contactSort,
                fn ($query) => $query
                    // vazios/nulos sempre no fim, independente da direção
                    ->orderByRaw("({$contactSort} IS NULL OR {$contactSort} = '') ASC")
                    ->orderBy($contactSort, $contactDir),
                fn ($query) => $query->latest()
            )
            ->paginate(10, ['*'], 'contacts_page')
            ->withQueryString();
    
        $opcionais = $client->opcionais()
            ->when($request->filled('opcional_search'), function ($query) use ($request) {
                $search = $request->opcional_search;
    
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('site', 'like', "%{$search}%")
                        ->orWhere('num_abac', 'like', "%{$search}%")
                        ->orWhere('num_sinac', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10, ['*'], 'opcionais_page')
            ->withQueryString();
    
        $client->load([
            'regional',
            'filiacoesHistorico',
            'redesSociais',
            'contratos',
            'socios',
            'juridicoContatos',
            'comites.contato',
            'tags',
        ]);

        $allTags = \App\Models\Tag::orderBy('nome')->get();

        $auditLogs = $activeTab === 'uso_interno'
            ? $client->auditLogs()
                ->with('user:id,name')
                ->latest('created_at')
                ->paginate(25, ['*'], 'audit_page')
                ->withQueryString()
            : collect();

        return view('clients.show', compact(
            'client',
            'documents',
            'addresses',
            'contacts',
            'contactSort',
            'contactDir',
            'opcionais',
            'activeTab',
            'activeSubtab',
            'gedCategory',
            'allTags',
            'auditLogs'
        ));
    }
    

    public function edit(Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        return view('clients.edit', [
            'client' => $client,
            'regionais' => $this->regionais(),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $client->update($request->validated() + [
            'status' => $request->boolean('status', $client->status),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'geral'])
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Client $client)
    {
        abort_unless(auth()->user()->can('clients.delete'), 403);

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Cliente removido com sucesso.');
    }
}
