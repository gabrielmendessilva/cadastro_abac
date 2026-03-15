<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientEndereco;
use Illuminate\Http\Request;

class ClientAddressController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'cep' => ['nullable', 'string', 'max:20'],
            'rua' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'pais' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:100'],
            'cod_ibge' => ['nullable', 'string', 'max:50'],
            'municipio' => ['nullable', 'string', 'max:255'],
        ]);

        $data['client_id'] = $client->id;

        ClientEndereco::create($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'enderecos'])
            ->with('success', 'Endereço adicionado com sucesso.');
    }

    public function update(Request $request, Client $client, ClientEndereco $address)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($address->client_id !== $client->id, 404);

        $data = $request->validate([
            'cep' => ['nullable', 'string', 'max:20'],
            'rua' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'pais' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:100'],
            'cod_ibge' => ['nullable', 'string', 'max:50'],
            'municipio' => ['nullable', 'string', 'max:255'],
        ]);

        $address->update($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'enderecos'])
            ->with('success', 'Endereço atualizado com sucesso.');
    }

    public function destroy(Client $client, ClientEndereco $address)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($address->client_id !== $client->id, 404);

        $address->delete();

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'enderecos'])
            ->with('success', 'Endereço removido com sucesso.');
    }
}
