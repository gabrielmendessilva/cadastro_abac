<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientRedeSocial;
use Illuminate\Http\Request;

class ClientRedeSocialController extends Controller
{
    public function store(Request $request, Client $client)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);

        $data = $request->validate([
            'tipo' => ['required', 'in:site,linkedin,instagram,facebook,outros'],
            'rotulo' => ['nullable', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
        ]);

        $client->redesSociais()->create($data);

        return back()->with('success', 'Rede social adicionada.');
    }

    public function update(Request $request, Client $client, ClientRedeSocial $rede)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($rede->client_id !== $client->id, 404);

        $data = $request->validate([
            'tipo' => ['required', 'in:site,linkedin,instagram,facebook,outros'],
            'rotulo' => ['nullable', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
        ]);

        $rede->update($data);

        return back()->with('success', 'Rede social atualizada.');
    }

    public function destroy(Client $client, ClientRedeSocial $rede)
    {
        abort_unless(auth()->user()->can('clients.edit'), 403);
        abort_if($rede->client_id !== $client->id, 404);

        $rede->delete();

        return back()->with('success', 'Rede social removida.');
    }
}
