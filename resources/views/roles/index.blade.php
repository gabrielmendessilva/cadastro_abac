@extends('layouts.app')
@section('title', 'Perfis e Permissões')
@section('page-title', 'Perfis e Permissões')

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        🔒 Área restrita: apenas usuários com perfil <strong>Root</strong> podem editar perfis e permissões.
        O perfil <strong>Root</strong> recebe todas as permissões automaticamente e não pode ser editado nem removido.
    </div>

    {{-- Criar novo perfil --}}
    <div x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Perfis cadastrados</h3>
                <p class="text-sm text-slate-500">Marque as permissões e clique em <em>Salvar permissões</em>.</p>
            </div>
            <button @click="open = true"
                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                + Novo perfil
            </button>
        </div>

        <div x-show="open" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click.away="open = false" class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                    <form method="POST" action="{{ route('roles.store') }}">
                        @csrf
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Novo perfil</h3>
                        </div>
                        <div class="p-6">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nome do perfil *</label>
                            <input type="text" name="name" required placeholder="Ex.: Financeiro, Jurídico..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="open = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Criar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Matriz de permissões --}}
    <form method="POST" action="{{ route('roles.sync') }}" class="rounded-2xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Permissão</th>
                        @foreach ($roles as $role)
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="{{ $role->name === 'Root' ? 'text-rose-700' : 'text-slate-700' }}">
                                        {{ $role->name }}
                                    </span>
                                    @if ($role->name !== 'Root')
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                              onsubmit="return confirm('Remover o perfil {{ $role->name }}? Usuários ficarão sem perfil.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-[10px] text-rose-600 hover:underline">remover</button>
                                        </form>
                                    @else
                                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] text-rose-700">protegido</span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($permissionGroups as $grupo => $permissoes)
                        <tr class="bg-slate-50">
                            <td colspan="{{ 1 + $roles->count() }}" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ ucfirst($grupo) }}
                            </td>
                        </tr>
                        @foreach ($permissoes as $perm)
                            <tr>
                                <td class="px-4 py-3 text-slate-700">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $perm->name }}</code>
                                    @php
                                        $partes = explode('.', $perm->name);
                                        $verbo = $partes[1] ?? null;
                                        $rotulo = match ($verbo) {
                                            'view'   => 'Visualizar',
                                            'create' => 'Criar',
                                            'edit'   => 'Editar',
                                            'delete' => 'Excluir',
                                            default  => $perm->name,
                                        };
                                    @endphp
                                    <span class="ml-2 text-xs text-slate-500">{{ $rotulo }}</span>
                                </td>
                                @foreach ($roles as $role)
                                    @php $isRoot = $role->name === 'Root'; @endphp
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox"
                                               name="roles[{{ $role->id }}][]"
                                               value="{{ $perm->name }}"
                                               {{ $role->permissions->contains('name', $perm->name) || $isRoot ? 'checked' : '' }}
                                               {{ $isRoot ? 'disabled' : '' }}
                                               class="h-4 w-4 rounded border-slate-300 {{ $isRoot ? 'opacity-60' : '' }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <a href="{{ route('roles.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</a>
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Salvar permissões
            </button>
        </div>
    </form>

    {{-- Perfis personalizados (auto-gerados pela tela de permissões do usuário) --}}
    @if ($customRoles->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Perfis personalizados por usuário</h3>
            <p class="text-xs text-slate-500 mb-4">
                Gerados automaticamente quando você ajusta as permissões de um usuário e elas não batem com nenhum perfil padrão.
                Edite entrando em <strong>Usuários → 🔑 Permissões</strong>.
            </p>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Perfil</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Usuário(s)</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Permissões</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customRoles as $cr)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs">{{ $cr->name }}</td>
                                <td class="px-3 py-2 text-xs">
                                    @foreach ($cr->users as $u)
                                        <a href="{{ route('users.permissions.edit', $u) }}" class="text-blue-600 hover:underline">{{ $u->name }}</a>{{ !$loop->last ? ', ' : '' }}
                                    @endforeach
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-500">
                                    {{ $cr->permissions->pluck('name')->implode(', ') ?: '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
