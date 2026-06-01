@extends('layouts.app')
@section('title', 'Permissões — ' . $user->name)
@section('page-title', 'Permissões de ' . $user->name)

@section('content')
<div class="space-y-6">
    {{-- Cabeçalho do usuário --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">{{ $user->name }}</h3>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">
                        Perfil: {{ $currentRole ?? 'sem perfil' }}
                    </span>
                    <span class="rounded-full {{ $user->status ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} px-2 py-0.5">
                        {{ $user->status ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>
            <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                ← Voltar para usuários
            </a>
        </div>
    </div>

    {{-- Trocar perfil --}}
    <form method="POST" action="{{ route('users.role.update', $user) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf @method('PUT')
        <h3 class="text-sm font-semibold text-slate-700">Trocar perfil</h3>
        <p class="text-xs text-slate-500 mb-3">Altera a role principal do usuário. As permissões herdadas mudam imediatamente.</p>
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-medium text-slate-600">Perfil</label>
                <select name="role" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    @foreach ($allRoles as $role)
                        @php
                            $podeSelecionar = !($role->name === 'Root' && !auth()->user()->isRoot());
                        @endphp
                        <option value="{{ $role->name }}"
                                @selected($currentRole === $role->name)
                                @disabled(!$podeSelecionar)>
                            {{ $role->name }}{{ $podeSelecionar ? '' : ' (somente Root)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700">
                Trocar perfil
            </button>
        </div>
    </form>

    {{-- Legenda --}}
    <div class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <p class="font-semibold mb-1">Como funciona:</p>
        <ul class="ml-4 list-disc text-xs space-y-0.5">
            <li>Marque exatamente as permissões que este usuário deve ter.</li>
            <li>Se as marcadas baterem com um perfil padrão (<strong>Administrador</strong>, <strong>Operador</strong> ou <strong>Consulta</strong>), esse perfil será atribuído automaticamente.</li>
            <li>Se forem diferentes, será criado um perfil <strong>Personalizado-{{ $user->name }}</strong> com essas permissões e atribuído só a este usuário.</li>
            <li>Para mudar o que cada perfil padrão entrega, vá em <a href="{{ route('roles.index') }}" class="underline font-medium">Perfis &amp; Permissões</a>.</li>
        </ul>
    </div>

    {{-- Matriz --}}
    <form method="POST" action="{{ route('users.permissions.update', $user) }}"
          x-data="{
              all: false,
              toggleAll() {
                  this.$root.querySelectorAll('input[type=checkbox][data-perm]')
                      .forEach(cb => cb.checked = this.all);
              }
          }"
          class="rounded-2xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
        @csrf @method('PUT')

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Permissão</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500 w-32">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="all" @change="toggleAll()" class="h-3.5 w-3.5 rounded border-slate-300">
                                <span class="text-[10px] normal-case font-medium text-slate-400">marcar todas</span>
                            </label>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($permissionGroups as $grupo => $permissoes)
                        <tr class="bg-slate-50">
                            <td colspan="2" class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ ucfirst($grupo) }}
                            </td>
                        </tr>
                        @foreach ($permissoes as $perm)
                            @php
                                $marcada = $currentPermissionNames->contains($perm->name);
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
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $perm->name }}</code>
                                    <span class="ml-2 text-xs text-slate-500">{{ $rotulo }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <input type="checkbox"
                                           data-perm
                                           name="permissions[]"
                                           value="{{ $perm->name }}"
                                           {{ $marcada ? 'checked' : '' }}
                                           class="h-4 w-4 rounded border-slate-300">
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-xs text-slate-500">
                ⓘ Ao salvar, o sistema decide automaticamente entre aplicar um perfil padrão ou criar um <em>Personalizado-{{ $user->name }}</em>.
            </p>
            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</a>
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Salvar permissões
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
