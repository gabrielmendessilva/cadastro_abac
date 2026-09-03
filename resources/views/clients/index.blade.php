@extends('layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold">Gestão de clientes</h3>
            <p class="text-slate-500 text-sm">Cadastro online completo dos clientes.</p>
        </div>
        @can('clients.create')
            <a href="{{ route('clients.create') }}" class="rounded-2xl bg-indigo-600 px-5 py-3 text-white">Novo cliente</a>
        @endcan
    </div>

    {{-- Painel de filtros em três faixas: escopo (o que estou olhando), refino
         (como estou estreitando) e ações. As classes aqui são só as que já
         existem no CSS compilado — o Tailwind é buildado na imagem e não
         recompila num restart, então classe nova é classe morta. --}}
    <form method="GET" class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- FAIXA 1 — escopo --}}
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Escopo da lista</span>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Tipo de cadastro: os botões da tela de Cadastro do sistema antigo.
                     Marcar mais de um soma as fatias; "Outras Empresas" é a base
                     inteira, então marcar ele solta a restrição (ver ClientController). --}}
                @foreach(\App\Models\Client::GRUPOS_CATEGORIA as $chave => $grupo)
                    <label class="flex cursor-pointer items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700">
                        <input type="checkbox" name="tipo[]" value="{{ $chave }}" @checked(in_array($chave, $tiposSelecionados, true)) class="h-4 w-4 shrink-0 rounded border-slate-300">
                        {{ $grupo['rotulo'] }}
                    </label>

                    @if($chave === 'administradoras')
                        {{-- Associadas/Não Associadas vêm logo depois de Administradoras
                             porque na tela antiga eram a mesma pergunta: Administradoras
                             (S) / (N). Filtram `associado_abac`, que é outro eixo — valem
                             para qualquer tipo marcado, não só para administradoras.

                             Cada caixa leva um hidden de par: checkbox desmarcada não é
                             enviada, e sem o valor 0 o controller não saberia distinguir
                             "desmarquei" de "abri a tela agora". O hidden vem antes de
                             propósito — com as duas chaves na URL, vale a última.
                             Marcar as duas (ou nenhuma) cobre a base inteira e o filtro
                             sai de cena; a tela abre com Associadas marcada. --}}
                        <label class="flex cursor-pointer items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700">
                            <input type="hidden" name="associadas" value="0">
                            <input type="checkbox" name="associadas" value="1" @checked($mostraAssociadas) class="h-4 w-4 shrink-0 rounded border-slate-300">
                            Associadas
                        </label>

                        <label class="flex cursor-pointer items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700">
                            <input type="hidden" name="nao_associadas" value="0">
                            <input type="checkbox" name="nao_associadas" value="1" @checked($mostraNaoAssociadas) class="h-4 w-4 shrink-0 rounded border-slate-300">
                            Não Associadas
                        </label>

                        {{-- Divisor: fecha o bloco das administradoras; o que vem depois
                             são os demais tipos de cadastro. --}}
                        <span class="hidden h-4 border-l border-slate-300 lg:block"></span>
                    @endif
                @endforeach
            </div>

            <span class="mt-2 block text-xs text-slate-500">Sem nenhum tipo marcado, a lista mostra todos os tipos de cadastro.</span>
        </div>

        {{-- FAIXA 2 — refino. Busca e Cidade valem o dobro de UF e Status em xl;
             em md viram uma grade 2x2; no celular, uma coluna. --}}
        <div class="px-4 py-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                <div class="min-w-0 xl:col-span-2">
                    <label for="filtro-busca" class="mb-1 block text-xs font-medium text-slate-500">Busca</label>
                    <input id="filtro-busca" type="text" name="search" value="{{ request('search') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900"
                           placeholder="Nome, CPF/CNPJ ou e-mail">
                </div>

                <div class="min-w-0 xl:col-span-2">
                    <label for="filtro-cidade" class="mb-1 block text-xs font-medium text-slate-500">Cidade</label>
                    <input id="filtro-cidade" type="text" name="city" value="{{ request('city') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900"
                           placeholder="Cidade">
                </div>

                <div class="min-w-0">
                    <label for="filtro-uf" class="mb-1 block text-xs font-medium text-slate-500">UF</label>
                    <input id="filtro-uf" type="text" name="state" value="{{ request('state') }}" maxlength="2"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm uppercase text-slate-900"
                           placeholder="UF">
                </div>

                {{-- $filtroStatus, e não request(): sem parâmetro na URL a lista já
                     vem filtrada em Ativo, e o select tem que mostrar isso (ver
                     ClientController::filtroComPadrao). A opção vazia diz "Todos"
                     porque o rótulo acima já diz "Status" — repetir faria o estado
                     sem filtro parecer um valor escolhido. --}}
                <div class="min-w-0">
                    <label for="filtro-status" class="mb-1 block text-xs font-medium text-slate-500">Status</label>
                    <select id="filtro-status" name="status"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtroStatus === '1')>Ativo</option>
                        <option value="0" @selected($filtroStatus === '0')>Inativo</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- FAIXA 3 — ações, ancoradas no rodapé do painel. --}}
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
            <a href="{{ route('clients.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700">Limpar</a>
            <button class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Buscar</button>
        </div>

    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    <th class="py-3 pr-4">Nome</th>
                    <th class="py-3 pr-4">Documento</th>
                    <th class="py-3 pr-4">Cidade/UF</th>
                    <th class="py-3 pr-4">Docs</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    @php $endereco = $client->enderecos->first(); @endphp
                    <tr class="border-b border-slate-100">
                        <td class="py-4 pr-4">
                            <div class="font-medium">{{ $client->name ?: $client->fantasy_name ?: '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $client->email ?: '-' }}</div>
                        </td>
                        <td class="py-4 pr-4">{{ $client->document ?: '-' }}</td>
                        <td class="py-4 pr-4">{{ trim(($endereco?->municipio ?: '-') . ' / ' . ($endereco?->estado ?: '-')) }}</td>
                        <td class="py-4 pr-4">{{ $client->documents_count }}</td>
                        <td class="py-4 pr-4">{{ $client->status ? 'Ativo' : 'Inativo' }}</td>
                        <td class="py-4 pr-4 flex gap-2 flex-wrap">
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'geral']) }}" class="rounded-xl border px-3 py-2">Ver</a>
                            @can('documents.create')
                                <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'ged']) }}" class="rounded-xl border px-3 py-2">GED</a>
                            @endcan
                            @can('clients.edit')
                                <a href="{{ route('clients.edit', $client) }}" class="rounded-xl border px-3 py-2">Editar</a>
                            @endcan
                            @can('clients.delete')
                                <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Excluir cliente?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">Excluir</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 text-center text-slate-500">
                            Nenhum cliente encontrado.
                            {{-- Busca vazia com os filtros padrão ligados engana: o cliente pode
                                 existir e estar fora de Associado (S) + Ativo. Mostra o que está
                                 filtrando e oferece a mesma busca sem filtro. --}}
                            @php
                                $filtrosAtivos = array_filter([
                                    $filtroStatus === null ? null : ($filtroStatus === '1' ? 'Ativo' : 'Inativo'),
                                    $vinculoExigido === null ? null : ($vinculoExigido ? 'Associadas' : 'Não Associadas'),
                                ]);
                            @endphp
                            @if($filtrosAtivos !== [])
                                <div class="mt-2 text-xs">
                                    A lista está filtrada em <span class="font-medium">{{ implode(' + ', $filtrosAtivos) }}</span>.
                                    <a href="{{ route('clients.index', array_merge(request()->except('page'), ['status' => '', 'associadas' => '1', 'nao_associadas' => '1'])) }}" class="text-indigo-600 underline">Buscar em todos os clientes</a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $clients->links() }}</div>
</div>
@endsection
