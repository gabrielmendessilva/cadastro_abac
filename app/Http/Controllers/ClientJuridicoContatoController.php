<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientJuridicoContato;
use Illuminate\Http\Request;

class ClientJuridicoContatoController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'area' => ['required', 'in:juridico,sinac'],
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ]);

        $client->juridicoContatos()->create($data);

        return back()->with('success', 'Contato adicionado.');
    }

    public function update(Request $request, Client $client, ClientJuridicoContato $contato)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($contato->client_id !== $client->id, 404);

        $data = $request->validate([
            'area' => ['required', 'in:juridico,sinac'],
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
        ]);

        $contato->update($data);

        return back()->with('success', 'Contato atualizado.');
    }

    public function destroy(Client $client, ClientJuridicoContato $contato)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($contato->client_id !== $client->id, 404);

        $contato->delete();

        return back()->with('success', 'Contato removido.');
    }
}
