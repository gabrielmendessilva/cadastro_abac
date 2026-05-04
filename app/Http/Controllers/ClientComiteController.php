<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientComite;
use Illuminate\Http\Request;

class ClientComiteController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'contato_id' => ['nullable', 'integer', 'exists:client_contatos,id'],
            'comite_nome' => ['required', 'string', 'max:255'],
            'papel' => ['required', 'in:coordenador,titular,suplente'],
            'observacoes' => ['nullable', 'string'],
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
            'comite_nome' => ['required', 'string', 'max:255'],
            'papel' => ['required', 'in:coordenador,titular,suplente'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $comite->update($data);

        return back()->with('success', 'Comitê atualizado.');
    }

    public function destroy(Client $client, ClientComite $comite)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($comite->client_id !== $client->id, 404);

        $comite->delete();

        return back()->with('success', 'Comitê removido.');
    }
}
