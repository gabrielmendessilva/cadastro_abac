<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enquanto a senha temporária do primeiro acesso não for trocada, o usuário só
 * enxerga a tela de troca (e o logout). As rotas de /trocar-senha ficam fora do
 * grupo que aplica este middleware — ver routes/web.php.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if ($request->expectsJson()) {
                abort(403, 'Troque a senha temporária para continuar usando o sistema.');
            }

            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
