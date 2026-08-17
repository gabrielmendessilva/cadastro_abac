@extends('layouts.app')
@section('title', 'Aniversariantes')
@section('page-title', 'Aniversariantes')

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold">Aniversariantes de {{ $consulta->nomeDoMes() }}</h3>
            <p class="text-slate-500 text-sm">
                Contatos das administradoras <strong>ativas</strong> que fazem aniversário no mês.
                Entram tanto os que têm data de nascimento completa quanto os que só têm o dia/mês.
            </p>
        </div>

        <a href="{{ route('aniversariantes.export', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-white hover:bg-emerald-700">
            ⬇ Exportar Excel
        </a>
    </div>

    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-4">
        <select name="mes" class="rounded-2xl border border-slate-300 px-4 py-3">
            @foreach ($meses as $numero => $nome)
                <option value="{{ $numero }}" @selected($consulta->mes === $numero)>{{ $nome }}</option>
            @endforeach
        </select>

        <select name="associado" class="rounded-2xl border border-slate-300 px-4 py-3">
            <option value="">Administradora</option>
            <option value="1" @selected(request('associado') === '1')>Associado (S)</option>
            <option value="0" @selected(request('associado') === '0')>Não associado (N)</option>
        </select>

        <input type="text" name="busca" value="{{ request('busca') }}"
               class="rounded-2xl border border-slate-300 px-4 py-3"
               placeholder="Empresa, contato ou e-mail">

        <div class="flex gap-3">
            <button class="w-full rounded-2xl bg-slate-900 px-5 py-3 text-white">Buscar</button>
            <a href="{{ route('aniversariantes.index') }}" class="rounded-2xl border px-5 py-3">Limpar</a>
        </div>
    </form>

    <p class="mb-4 text-sm text-slate-500">
        {{ $linhas->count() }} {{ $linhas->count() === 1 ? 'aniversariante' : 'aniversariantes' }}
        em {{ $consulta->nomeDoMes() }}.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="py-3 pr-4">Dia</th>
                    <th class="py-3 pr-4">Contato</th>
                    <th class="py-3 pr-4">Empresa</th>
                    <th class="py-3 pr-4">Cidade/UF</th>
                    <th class="py-3 pr-4">E-mail</th>
                    <th class="py-3 pr-4">Telefone</th>
                    <th class="py-3 pr-4">Associado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($linhas as $linha)
                    <tr class="border-b border-slate-100">
                        <td class="py-4 pr-4 whitespace-nowrap font-medium">
                            {{ $linha['dia'] ? str_pad((string) $linha['dia'], 2, '0', STR_PAD_LEFT) . '/' . str_pad((string) $consulta->mes, 2, '0', STR_PAD_LEFT) : '-' }}
                            @if ($linha['dt_nascimento'])
                                <div class="text-xs font-normal text-slate-500">
                                    {{ \Carbon\Carbon::parse($linha['dt_nascimento'])->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td class="py-4 pr-4">
                            <div class="font-medium">{{ $linha['contato'] ?: '-' }}</div>
                            @if ($linha['funcao'] || $linha['departamento'])
                                <div class="text-xs text-slate-500">{{ collect([$linha['funcao'], $linha['departamento']])->filter()->implode(' · ') }}</div>
                            @endif
                        </td>
                        <td class="py-4 pr-4">
                            <div>{{ $linha['empresa'] ?: '-' }}</div>
                            @if ($linha['categoria'])
                                <div class="text-xs text-slate-500">{{ $linha['categoria'] }}</div>
                            @endif
                        </td>
                        <td class="py-4 pr-4 whitespace-nowrap">{{ trim(($linha['cidade'] ?: '-') . ' / ' . ($linha['uf'] ?: '-')) }}</td>
                        <td class="py-4 pr-4">{{ $linha['email'] ?: '-' }}</td>
                        <td class="py-4 pr-4 whitespace-nowrap">
                            {{ $linha['celular'] ?: ($linha['telefone_contato'] ?: ($linha['telefone_empresa'] ?: '-')) }}
                        </td>
                        <td class="py-4 pr-4">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $linha['associado_abac'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $linha['associado_abac'] ? 'Sim' : 'Não' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-500">
                            Nenhum aniversariante em {{ $consulta->nomeDoMes() }} com os filtros atuais.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
