@extends('layouts.guest')

@section('content')
<div class="max-w-md mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Entrar</h2>
        <p class="text-slate-500 mt-2">Use seu e-mail e senha para acessar o sistema.</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-2">E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none" required>
            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Senha</label>
            <input type="password" name="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none" required>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1"> Lembrar acesso
        </label>

        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-white font-semibold hover:bg-slate-800">Acessar sistema</button>
    </form>

    <div class="mt-8 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
        <p><strong>Login padrão:</strong> admin@sistema.local</p>
        <p><strong>Senha:</strong> password</p>
    </div>
</div>
@endsection
