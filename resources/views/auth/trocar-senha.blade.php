@extends('layouts.guest')

@section('title', 'Trocar senha - Cadastro ABAC')

@section('content')
<div class="max-w-md mx-auto">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-slate-900">Crie sua senha</h2>
        <p class="text-slate-500 mt-2">
            Este é o seu primeiro acesso. Defina uma senha pessoal para continuar —
            a senha temporária que você recebeu por e-mail deixa de valer depois disso.
        </p>
    </div>

    <div class="mb-6 rounded-2xl bg-slate-100 px-4 py-3 text-sm text-slate-600">
        Entrando como <span class="font-semibold text-slate-900">{{ auth()->user()->email }}</span>
    </div>

    <form method="POST" action="{{ route('password.change.store') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-2">Senha temporária (a que veio no e-mail)</label>
            <input type="password" name="current_password" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none" required autofocus>
            @error('current_password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Nova senha</label>
            <input type="password" name="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none" required>
            <p class="text-slate-500 text-xs mt-1">Mínimo de 8 caracteres.</p>
            @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Confirmar nova senha</label>
            <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:outline-none" required>
        </div>

        <button class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-white font-semibold hover:bg-slate-800">Salvar nova senha</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button class="text-sm text-slate-500 hover:text-slate-800 underline">Sair e voltar ao login</button>
    </form>
</div>
@endsection
