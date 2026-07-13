<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup público de Client por CPF/CNPJ.
 *
 * ⚠️ SEM AUTENTICAÇÃO — paridade de comportamento com o projeto de origem
 * (abac_admin), onde a rota `/api/users/find` era pública. Isso permite que
 * qualquer visitante enumere clientes por documento. Débito técnico:
 * mover para trás de middleware('auth') + permissão `client.view` assim
 * que confirmarmos que o único consumidor real é o próprio front interno.
 */
class ClientLookupController extends Controller
{
    public function findByDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'document' => ['required', 'string', 'max:20'],
        ]);

        // Só dígitos, limitando a 14 caracteres (CPF=11, CNPJ=14).
        $doc = substr(preg_replace('/\D/', '', $data['document']) ?? '', 0, 14);

        if ($doc === '') {
            return response()->json(null);
        }

        $client = Client::query()->where('cpf_cnpj', $doc)->first();

        return response()->json($client);
    }
}
