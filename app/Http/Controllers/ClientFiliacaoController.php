<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientFiliacaoHistorico;
use Illuminate\Http\Request;

class ClientFiliacaoController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'tipo' => ['required', 'in:abac,sinac'],
            'num_filiacao' => ['nullable', 'string', 'max:50'],
            'dt_filiacao' => ['nullable', 'date'],
            'dt_desfiliacao' => ['nullable', 'date'],
            'motivo_desfiliacao' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $client->filiacoesHistorico()->create($data);

        return back()->with('success', 'Filiação anterior adicionada.');
    }

    public function update(Request $request, Client $client, ClientFiliacaoHistorico $filiacao)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($filiacao->client_id !== $client->id, 404);

        $data = $request->validate([
            'tipo' => ['required', 'in:abac,sinac'],
            'num_filiacao' => ['nullable', 'string', 'max:50'],
            'dt_filiacao' => ['nullable', 'date'],
            'dt_desfiliacao' => ['nullable', 'date'],
            'motivo_desfiliacao' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $filiacao->update($data);

        return back()->with('success', 'Filiação atualizada.');
    }

    public function destroy(Client $client, ClientFiliacaoHistorico $filiacao)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($filiacao->client_id !== $client->id, 404);

        $filiacao->delete();

        return back()->with('success', 'Filiação removida.');
    }
}
