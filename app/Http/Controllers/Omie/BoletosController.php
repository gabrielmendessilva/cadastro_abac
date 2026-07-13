<?php

namespace App\Http\Controllers\Omie;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Boletos de Contas a Receber.
 *
 * DECISÃO DE MIGRAÇÃO: no projeto de origem (abac_admin) esses endpoints eram
 * stubs que devolviam HTTP 200 com mensagem fixa. Aqui preservamos as URLs
 * (paridade) mas respondemos HTTP 501 Not Implemented até implementação real.
 *
 * Endpoint Omie relevante quando for implementar:
 *   POST https://app.omie.com.br/api/v1/financas/contareceberboleto/
 *   calls: IncluirBoleto, AlterarBoleto, CancelarBoleto
 */
class BoletosController extends Controller
{
    public function store(Request $request): never
    {
        abort(501, 'Boletos Contas a Receber (create) ainda não implementado.');
    }

    public function update(Request $request): never
    {
        abort(501, 'Boletos Contas a Receber (update) ainda não implementado.');
    }

    public function cancel(Request $request): never
    {
        abort(501, 'Boletos Contas a Receber (cancel) ainda não implementado.');
    }
}
