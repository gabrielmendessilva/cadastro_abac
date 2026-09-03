<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Rm\Support\Normalizer;
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
        $doc = substr(Normalizer::digits($data['document']), 0, 14);

        if ($doc === '') {
            return response()->json(null);
        }

        // `clients.document` guarda o documento formatado (51.855.716/0001-01),
        // então comparar só os dígitos nunca casaria. Busca pela máscara e cai
        // para o valor cru caso algum registro tenha entrado sem formatação.
        $client = Client::query()
            ->join('regionais', 'clients.regional_id','regionais.id' )
            ->where('document', Normalizer::formatCpfCnpj($doc))
            ->orWhere('document', $doc)
            ->select('clients.*', 'regionais.nome as regional')
            ->first();

        return response()->json($client);
    }


    public function updateByDocument(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cpf_cnpj' => ['required', 'string', 'max:20'],
            'cod_omie' => ['required', 'integer', 'min:1'],
        ]);

        // Só dígitos, limitando a 14 caracteres (CPF=11, CNPJ=14).
        $doc = substr(Normalizer::digits($data['cpf_cnpj']), 0, 14);

        if ($doc === '') {
            return response()->json(['message' => 'CPF/CNPJ inválido.'], 422);
        }

        // `clients.document` guarda o documento formatado (51.855.716/0001-01);
        // o fallback cobre registros que entraram sem máscara. O agrupamento é
        // obrigatório: sem ele o `orWhere` escaparia de futuras condições.
        $updated = Client::query()
            ->where(function ($query) use ($doc) {
                $query->where('document', Normalizer::formatCpfCnpj($doc))
                    ->orWhere('document', $doc);
            })
            ->update(['cod_omie' => $data['cod_omie']]);

        if ($updated === 0) {
            return response()->json(['message' => 'Cliente não encontrado.'], 404);
        }

        return response()->json([
            'updated' => $updated,
            'cod_omie' => (int) $data['cod_omie'],
        ]);
    }
}
