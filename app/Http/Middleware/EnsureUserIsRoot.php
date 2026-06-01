<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsRoot
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isRoot()) {
            abort(403, 'Apenas usuários Root podem acessar essa área.');
        }

        return $next($request);
    }
}
