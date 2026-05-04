<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $clients = Client::query()
            ->withCount('documents')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('fantasy_name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status === '1');
            })
            ->when($request->filled('state'), fn($query) => $query->where('state', $request->string('state')))
            ->when($request->filled('city'), fn($query) => $query->where('city', 'like', '%' . $request->string('city') . '%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('clients.create'), 403);

        return view('clients.create');
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
    
        $contacts = $client->contatos()
            ->when($request->filled('contact_search'), function ($query) use ($request) {
                $search = $request->contact_search;
    
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('nome', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%")
                        ->orWhere('telefone_2', 'like', "%{$search}%")
                        ->orWhere('funcao', 'like', "%{$search}%")
                        ->orWhere('departamento', 'like', "%{$search}%")
                        ->orWhere('outro_departamento', 'like', "%{$search}%");
                });
            })
            ->latest()
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

        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $client->update($request->validated() + [
            'status' => $request->boolean('status', true),
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
