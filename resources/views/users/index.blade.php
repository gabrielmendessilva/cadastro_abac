@extends('layouts.app')
@section('title', 'Usuários')
@section('page-title', 'Usuários')

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold">Gestão de usuários</h3>
            <p class="text-slate-500 text-sm">Controle completo de usuários e perfis.</p>
        </div>
        @can('users.create')<a href="{{ route('users.create') }}" class="rounded-2xl bg-indigo-600 px-5 py-3 text-white">Novo usuário</a>@endcan
    </div>

    <form method="GET" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="search" value="{ request('search') }" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Buscar...">
            <button class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Buscar</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="py-3 pr-4">Nome</th>
                    <th class="py-3 pr-4">E-mail</th>
                    <th class="py-3 pr-4">Perfil</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-slate-100">
                        <td class="py-4 pr-4">{{ $user->name }}</td>
                        <td class="py-4 pr-4">{{ $user->email }}</td>
                        <td class="py-4 pr-4">{{ $user->getRoleNames()->first() ?? '-' }}</td>
                        <td class="py-4 pr-4">{{ $user->status ? 'Ativo' : 'Inativo' }}</td>
                        <td class="py-4 pr-4 flex gap-2 flex-wrap">
                            <a href="{{ route('users.show', $user) }}" class="rounded-xl border px-3 py-2">Ver</a>
                            @can('users.edit')<a href="{{ route('users.edit', $user) }}" class="rounded-xl border px-3 py-2">Editar</a>@endcan
                            @can('users.delete')
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Excluir usuário?')">
                                @csrf @method('DELETE')
                                <button class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">Excluir</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-slate-500">Nenhum usuário encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
@endsection
