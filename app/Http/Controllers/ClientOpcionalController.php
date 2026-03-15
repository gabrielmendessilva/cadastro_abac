<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientOpcional;
use Illuminate\Http\Request;

class ClientOpcionalController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'site' => ['nullable', 'string', 'max:255'],
            'inicio_atv' => ['nullable', 'date'],
            'num_abac' => ['nullable', 'string', 'max:255'],
            'dt_f_abac' => ['nullable', 'date'],
            'num_sinac' => ['nullable', 'string', 'max:255'],
            'dt_f_sinac' => ['nullable', 'date'],
        ]);

        $data['client_id'] = $client->id;

        ClientOpcional::create($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'opcionais'])
            ->with('success', 'Registro opcional adicionado com sucesso.');
    }

    public function update(Request $request, Client $client, ClientOpcional $opcional)
    {
        abort_if($opcional->client_id !== $client->id, 404);

        $data = $request->validate([
            'site' => ['nullable', 'string', 'max:255'],
            'inicio_atv' => ['nullable', 'date'],
            'num_abac' => ['nullable', 'string', 'max:255'],
            'dt_f_abac' => ['nullable', 'date'],
            'num_sinac' => ['nullable', 'string', 'max:255'],
            'dt_f_sinac' => ['nullable', 'date'],
        ]);

        $opcional->update($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'opcionais'])
            ->with('success', 'Registro opcional atualizado com sucesso.');
    }

    public function destroy(Client $client, ClientOpcional $opcional)
    {
        abort_if($opcional->client_id !== $client->id, 404);

        $opcional->delete();

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'opcionais'])
            ->with('success', 'Registro opcional removido com sucesso.');
    }
}
