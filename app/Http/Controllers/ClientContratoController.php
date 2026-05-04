<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContrato;
use Illuminate\Http\Request;

class ClientContratoController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'dt_vencimento' => ['nullable', 'date'],
            'ativo' => ['nullable', 'boolean'],
            'observacoes' => ['nullable', 'string'],
        ]);
        $data['ativo'] = $request->boolean('ativo', true);

        $client->contratos()->create($data);

        return back()->with('success', 'Contrato adicionado.');
    }

    public function update(Request $request, Client $client, ClientContrato $contrato)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($contrato->client_id !== $client->id, 404);

        $data = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'dt_vencimento' => ['nullable', 'date'],
            'ativo' => ['nullable', 'boolean'],
            'observacoes' => ['nullable', 'string'],
        ]);
        $data['ativo'] = $request->boolean('ativo', false);

        $contrato->update($data);

        return back()->with('success', 'Contrato atualizado.');
    }

    public function destroy(Client $client, ClientContrato $contrato)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($contrato->client_id !== $client->id, 404);

        $contrato->delete();

        return back()->with('success', 'Contrato removido.');
    }
}
