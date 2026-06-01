<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    /**
     * Busca um CEP usando as APIs ViaCEP e OpenCEP como fallback.
     * Retorna JSON normalizado para preencher o form de endereço.
     */
    public function show(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json([
                'ok'      => false,
                'message' => 'CEP deve ter 8 dígitos.',
            ], 422);
        }

        $fetch = function (string $url): ?array {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url);

                if (!$response->ok()) {
                    return null;
                }

                $json = $response->json();

                return is_array($json) ? $json : null;
            } catch (\Throwable $e) {
                return null;
            }
        };

        // 1) ViaCEP
        $viacep = $fetch("https://viacep.com.br/ws/{$cep}/json/");
        if ($viacep && empty($viacep['erro']) && !empty($viacep['cep'])) {
            return response()->json([
                'ok'         => true,
                'fonte'      => 'viacep',
                'cep'        => $viacep['cep']       ?? null,
                'rua'        => $viacep['logradouro'] ?? '',
                'complemento'=> $viacep['complemento']?? '',
                'bairro'     => $viacep['bairro']    ?? '',
                'municipio'  => $viacep['localidade']?? '',
                'estado'     => $viacep['uf']        ?? '',
                'cod_ibge'   => $viacep['ibge']      ?? '',
                'pais'       => 'Brasil',
            ]);
        }

        // 2) OpenCEP fallback
        $opencep = $fetch("https://opencep.com/v1/{$cep}.json");
        if ($opencep && !empty($opencep['cep'])) {
            return response()->json([
                'ok'         => true,
                'fonte'      => 'opencep',
                'cep'        => $opencep['cep']        ?? null,
                'rua'        => $opencep['logradouro'] ?? '',
                'complemento'=> $opencep['complemento']?? '',
                'bairro'     => $opencep['bairro']     ?? '',
                'municipio'  => $opencep['localidade'] ?? '',
                'estado'     => $opencep['uf']         ?? '',
                'cod_ibge'   => $opencep['ibge']       ?? '',
                'pais'       => 'Brasil',
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => 'Não foi possível consultar o CEP. Digite manualmente os dados do endereço.',
        ], 404);
    }
}
