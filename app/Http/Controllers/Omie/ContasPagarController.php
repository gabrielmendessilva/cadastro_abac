<?php

namespace App\Http\Controllers\Omie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Contas a Pagar — Lançamentos.
 *
 * DECISÃO DE MIGRAÇÃO: no projeto de origem (abac_admin) esses endpoints eram
 * stubs que devolviam HTTP 200 com a mensagem literal "editContaReceber"
 * (copy-paste bug). Isso mascarava a ausência de implementação e enganava
 * consumidores. Aqui preservamos as URLs (paridade de contrato) mas respondemos
 * HTTP 501 Not Implemented, dando feedback correto até alguém implementar de fato.
 *
 * Endpoint Omie relevante quando for implementar:
 *   POST https://app.omie.com.br/api/v1/financas/contapagar/
 *   calls: IncluirContaPagar, AlterarContaPagar, ConsultarContaPagar, ExcluirContaPagar
 */
class ContasPagarController extends Controller
{
    public function store(Request $request): never
    {
        abort(501, 'Contas a Pagar (create) ainda não implementado.');
    }

    public function update(Request $request): never
    {
        abort(501, 'Contas a Pagar (update) ainda não implementado.');
    }

    public function show(Request $request): never
    {
        abort(501, 'Contas a Pagar (find) ainda não implementado.');
    }
}
