<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'root' => \App\Http\Middleware\EnsureUserIsRoot::class,
            'permissoes' => \App\Http\Middleware\EnsureUserManagesPermissions::class,
            'senha.trocada' => \App\Http\Middleware\EnsurePasswordIsChanged::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        // 419 (CSRF/sessão expirada): em vez da página de erro, volta ao
        // formulário com mensagem amigável. Acontece quando a página ficou
        // aberta além do SESSION_LIFETIME ou atravessou um deploy.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            return redirect()
                ->back()
                ->withInput($request->except('_token', 'password'))
                ->withErrors(['email' => 'Sua sessão expirou. Tente novamente.']);
        });
    })->create();
