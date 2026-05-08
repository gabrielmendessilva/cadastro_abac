@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Listas</h1>
            <p class="text-sm text-slate-500">Tabelas de domínio e relatórios.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 lg:flex-row">
        {{-- Sidebar --}}
        <aside class="lg:w-64 lg:shrink-0">
            @php
                $abas = [
                    'mandatos' => '📅 Mandatos próximos',
                    'integrantes_comites' => '👥 Integrantes de comitês',
                ];
            @endphp
            <nav class="sticky top-4 flex flex-col gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                <p class="px-3 pt-2 text-[10px] font-semibold uppercase text-slate-400">Relatórios</p>
                @foreach ($abas as $key => $label)
                    <a href="{{ route('listas.index', ['aba' => $key]) }}"
                       class="rounded-xl px-3 py-2 text-sm {{ $aba === $key ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach

                <p class="mt-3 px-3 pt-2 text-[10px] font-semibold uppercase text-slate-400">Tabelas de domínio</p>
                @foreach ($recursos as $key => $info)
                    <a href="{{ route('listas.index', ['aba' => $key]) }}"
                       class="rounded-xl px-3 py-2 text-sm {{ $aba === $key ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $info['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="flex-1 space-y-4">
            @if ($aba === 'mandatos')
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-lg font-semibold text-slate-900">Mandatos próximos de vencer</h2>
                    <p class="mb-4 text-sm text-slate-500">Clientes com mandato terminando nos próximos 90 dias e flag de aviso ativada.</p>

                    @if ($mandatos->isEmpty())
                        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Nenhum mandato vencendo.</p>
                    @else
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-xs">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Cliente</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Presidente</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Início</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Término</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Faltam</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($mandatos as $m)
                                        @php $dias = (int) now()->diffInDays(\Carbon\Carbon::parse($m->mandato_termino), false); @endphp
                                        <tr>
                                            <td class="px-3 py-2"><a href="{{ route('clients.show', ['client' => $m->id, 'tab' => 'secretaria']) }}" class="text-blue-600 hover:underline">{{ $m->nome }}</a></td>
                                            <td class="px-3 py-2">{{ $m->presidente_atual ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($m->mandato_inicio)->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($m->mandato_termino)->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2">
                                                <span class="rounded-full {{ $dias <= 30 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }} px-2 py-0.5 text-xs">
                                                    {{ $dias }} dia(s)
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            @if ($aba === 'integrantes_comites')
                <div class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h2 class="mb-3 text-lg font-semibold text-slate-900">Integrantes de comitês</h2>
                    @if ($integrantesComites->isEmpty())
                        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Nenhum comitê cadastrado ainda.</p>
                    @else
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-xs">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Comitê</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Cliente</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Contato</th>
                                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Papel</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($integrantesComites as $c)
                                        <tr>
                                            <td class="px-3 py-2 font-medium">{{ $c->comite_nome }}</td>
                                            <td class="px-3 py-2">{{ $c->client?->nome ?: '-' }}</td>
                                            <td class="px-3 py-2">{{ $c->contato?->nome ?: '-' }} <span class="text-xs text-slate-400">{{ $c->contato?->email ?: '' }}</span></td>
                                            <td class="px-3 py-2">
                                                <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">{{ \App\Models\ClientComite::PAPEIS[$c->papel] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tabelas de domínio --}}
            @if ($recursoAtual)
                <div x-data="{ openNew: false }" class="rounded-2xl border border-slate-200 bg-white p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900">{{ $recursoAtual['label'] }}</h2>
                        @can('clients.edit')
                            <button @click="openNew = true"
                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                + Adicionar
                            </button>
                        @endcan
                    </div>

                    @if ($itensRecurso->isEmpty())
                        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">Nenhum item cadastrado.</p>
                    @else
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-xs">
                                    <tr>
                                        @foreach ($recursoAtual['campos'] as $c)
                                            <th class="px-3 py-2 text-left font-semibold uppercase text-slate-500">{{ $c }}</th>
                                        @endforeach
                                        @can('clients.edit')<th></th>@endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($itensRecurso as $item)
                                        <tr>
                                            @foreach ($recursoAtual['campos'] as $c)
                                                <td class="px-3 py-2">{{ $item->{$c} ?: '-' }}</td>
                                            @endforeach
                                            @can('clients.edit')
                                                <td class="px-3 py-2 text-right">
                                                    <form method="POST" action="{{ route('listas.destroy', ['aba' => $aba, 'id' => $item->id]) }}" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button onclick="return confirm('Remover?')"
                                                                class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Remover</button>
                                                    </form>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $itensRecurso->links() }}
                    @endif

                    @can('clients.edit')
                        <div x-show="openNew" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="openNew = false" class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                                    <form method="POST" action="{{ route('listas.store', ['aba' => $aba]) }}">
                                        @csrf
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Novo item · {{ $recursoAtual['label'] }}</h3>
                                        </div>
                                        <div class="grid grid-cols-1 gap-4 p-6">
                                            @foreach ($recursoAtual['campos'] as $c)
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium uppercase text-slate-700">{{ $c }} {{ $loop->first ? '*' : '' }}</label>
                                                    <input type="text" name="{{ $c }}" {{ $loop->first ? 'required' : '' }}
                                                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                            <button type="button" @click="openNew = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
