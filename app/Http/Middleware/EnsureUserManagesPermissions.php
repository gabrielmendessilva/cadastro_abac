<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portão da área de perfis e permissões (Root e Administrador).
 *
 * Antes essa área era só do Root (EnsureUserIsRoot). O Administrador passou a
 * entrar porque é quem cuida do sistema no dia a dia — inclusive de mandar o
 * login dele direto para cá (App\Support\TelaInicial).
 */
class EnsureUserManagesPermissions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->podeGerenciarPermissoes()) {
            abort(403, 'Apenas Root e Administrador podem acessar essa área.');
        }

        return $next($request);
    }
}
