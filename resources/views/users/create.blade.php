@extends('layouts.app')
@section('title', 'Novo usuário')
@section('page-title', 'Novo usuário')
@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <form method="POST" action="{{ route('users.store') }}">@csrf
<div class="grid md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium mb-2">Nome</label>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
        @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">E-mail</label>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">Senha</label>
        <input type="password" name="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" {{ isset($user) ? '' : 'required' }}>
        @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">Confirmar senha</label>
        <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-300 px-4 py-3" {{ isset($user) ? '' : 'required' }}>
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">Perfil</label>
        <select name="role" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" @selected(old('role', isset($user) ? $user->getRoleNames()->first() : '') == $role->name)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center gap-3 mt-8">
        <input type="checkbox" name="status" value="1" @checked(old('status', $user->status ?? true))>
        <label>Usuário ativo</label>
    </div>
</div>
<div class="mt-6 flex gap-3">
    <button class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Salvar</button>
    <a href="{{ route('users.index') }}" class="rounded-2xl border px-5 py-3">Cancelar</a>
</div>
</form>
</div>
@endsection
