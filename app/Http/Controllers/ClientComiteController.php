<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientComite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class ClientComiteController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'contato_id' => ['nullable', 'integer', 'exists:client_contatos,id'],
            'comite_nome' => ['required', 'string', 'max:255', $this->semRepetir($client, $request)],
            'papel' => ['required', 'in:coordenador,titular,suplente'],
            'observacoes' => ['nullable', 'string'],
        ], [
            'comite_nome.unique' => 'Este contato já está vinculado a esse comitê.',
        ]);

        $client->comites()->create($data);

        return back()->with('success', 'Comitê adicionado.');
    }

    public function update(Request $request, Client $client, ClientComite $comite)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($comite->client_id !== $client->id, 404);

        $data = $request->validate([
            'contato_id' => ['nullable', 'integer', 'exists:client_contatos,id'],
            'comite_nome' => ['required', 'string', 'max:255', $this->semRepetir($client, $request)->ignore($comite)],
            'papel' => ['required', 'in:coordenador,titular,suplente'],
            'observacoes' => ['nullable', 'string'],
        ], [
            'comite_nome.unique' => 'Este contato já está vinculado a esse comitê.',
        ]);

        $comite->update($data);

        return back()->with('success', 'Comitê atualizado.');
    }

    /**
     * O par (contato, comitê) é único por cliente no banco desde a migration
     * 2026_08_10_000020. Sem esta regra o duplicado viraria erro 500 em vez de
     * mensagem no formulário. Vínculo sem contato não entra na trava — o índice
     * único do MySQL ignora nulos.
     */
    private function semRepetir(Client $client, Request $request): Unique
    {
        return Rule::unique('client_comites', 'comite_nome')
            ->where('client_id', $client->id)
            ->where('contato_id', $request->input('contato_id'));
    }

    public function destroy(Client $client, ClientComite $comite)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($comite->client_id !== $client->id, 404);

        $comite->delete();

        return back()->with('success', 'Comitê removido.');
    }
}
