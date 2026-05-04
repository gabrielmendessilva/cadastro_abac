<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientSocio;
use Illuminate\Http\Request;

class ClientSocioController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'papel' => ['required', 'in:socio,administrador'],
            'nome' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $client->socios()->create($data);

        return back()->with('success', 'Sócio/administrador adicionado.');
    }

    public function update(Request $request, Client $client, ClientSocio $socio)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($socio->client_id !== $client->id, 404);

        $data = $request->validate([
            'papel' => ['required', 'in:socio,administrador'],
            'nome' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $socio->update($data);

        return back()->with('success', 'Sócio/administrador atualizado.');
    }

    public function destroy(Client $client, ClientSocio $socio)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($socio->client_id !== $client->id, 404);

        $socio->delete();

        return back()->with('success', 'Sócio/administrador removido.');
    }
}
