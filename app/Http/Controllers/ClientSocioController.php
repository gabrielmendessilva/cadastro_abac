<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientSocio;
use Illuminate\Http\Request;

class ClientSocioController extends Controller
{
    private function rules(): array
    {
        return [
            'papel' => ['required', 'in:socio,administrador'],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'quota_participacao' => ['nullable', 'numeric', 'between:0,100'],
            'mandato_inicio' => ['nullable', 'date'],
            'mandato_termino' => ['nullable', 'date', 'after_or_equal:mandato_inicio'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate($this->rules());

        $client->socios()->create($data);

        return back()->with('success', 'Sócio/administrador adicionado.');
    }

    public function update(Request $request, Client $client, ClientSocio $socio)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($socio->client_id !== $client->id, 404);

        $data = $request->validate($this->rules());

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
