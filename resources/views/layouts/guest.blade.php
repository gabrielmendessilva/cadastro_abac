<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Login - Abac')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center p-6">
    <div class="w-full max-w-5xl grid lg:grid-cols-2 overflow-hidden rounded-3xl shadow-2xl bg-white">
        <div class="hidden lg:flex bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 text-white p-10 flex-col justify-between">
            <div>
                <span class="inline-block rounded-full bg-white/10 px-4 py-2 text-sm">Laravel + Blade</span>
                <h1 class="mt-6 text-4xl font-bold leading-tight">Cadastro online com GED por cliente</h1>
                <p class="mt-4 text-slate-300">Projeto com controle de acesso por nível de usuário, CRUD completo e gestão eletrônica de documentos.</p>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="rounded-2xl bg-white/10 p-4">Usuários e perfis</div>
                <div class="rounded-2xl bg-white/10 p-4">Clientes e documentos</div>
                <div class="rounded-2xl bg-white/10 p-4">Layout moderno</div>
                <div class="rounded-2xl bg-white/10 p-4">Permissões por acesso</div>
            </div>
        </div>
        <div class="p-8 lg:p-12">
            @yield('content')
        </div>
    </div>
</body>
</html>
