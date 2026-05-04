<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientContato;
use Illuminate\Http\Request;

class ClientContactController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'dt_nascimento' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'email_2' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'telefone_2' => ['nullable', 'string', 'max:30'],
            'ramal' => ['nullable', 'string', 'max:30'],
            'celular' => ['nullable', 'string', 'max:30'],
            'obs' => ['nullable', 'string'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'outro_departamento' => ['nullable', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'boolean'],
            'comite' => ['nullable', 'boolean'],
            'unlock_whatsApp' => ['nullable', 'boolean'],
        ]);

        $data['client_id'] = $client->id;
        $data['user_id'] = auth()->id();
        $data['representante_legal'] = $request->boolean('representante_legal');
        $data['comite'] = $request->boolean('comite');
        $data['unlock_whatsApp'] = $request->boolean('unlock_whatsApp');

        ClientContato::create($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'contatos'])
            ->with('success', 'Contato adicionado com sucesso.');
    }

    public function update(Request $request, Client $client, ClientContato $contact)
    {
        abort_if($contact->client_id !== $client->id, 404);

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'funcao' => ['nullable', 'string', 'max:255'],
            'dt_nascimento' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'email_2' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'telefone_2' => ['nullable', 'string', 'max:30'],
            'ramal' => ['nullable', 'string', 'max:30'],
            'celular' => ['nullable', 'string', 'max:30'],
            'obs' => ['nullable', 'string'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'outro_departamento' => ['nullable', 'string', 'max:255'],
            'representante_legal' => ['nullable', 'boolean'],
            'comite' => ['nullable', 'boolean'],
            'unlock_whatsApp' => ['nullable', 'boolean'],
        ]);

        $data['representante_legal'] = $request->boolean('representante_legal');
        $data['comite'] = $request->boolean('comite');
        $data['unlock_whatsApp'] = $request->boolean('unlock_whatsApp');

        $contact->update($data);

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'contatos'])
            ->with('success', 'Contato atualizado com sucesso.');
    }

    public function destroy(Client $client, ClientContato $contact)
    {
        abort_if($contact->client_id !== $client->id, 404);

        $contact->delete();

        return redirect()
            ->route('clients.show', ['client' => $client, 'tab' => 'contatos'])
            ->with('success', 'Contato removido com sucesso.');
    }
}
