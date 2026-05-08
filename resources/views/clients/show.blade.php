@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $client->nome }}</h1>
            <p class="text-sm text-slate-500">CPF/CNPJ: {{ $client->cpf_cnpj ?: '-' }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('clients.edit')
                <a href="{{ route('clients.edit', $client) }}"
                   class="inline-flex items-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600">
                    Editar cliente
                </a>
            @endcan

            <a href="{{ route('clients.index') }}"
               class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Voltar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <div class="font-semibold">Verifique os campos abaixo:</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-col gap-4 lg:flex-row">
        @include('clients.partials._sidebar')

        <div class="flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($activeTab === 'geral')
                <div x-data="{ openOpcionais: false }" class="space-y-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Dados gerais</h2>
                            <p class="text-sm text-slate-500">Informações principais do cliente.</p>
                        </div>
                        @can('clients.edit')
                            <button type="button" @click="openOpcionais = true"
                                    class="inline-flex items-center gap-1 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                ➕ Opcionais
                            </button>
                        @endcan
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @php
                            $fieldHints = [
                                'Código Omie' => ['hint' => 'Bloqueado para alterações acidentais. Use o botão Editar para modificar.', 'locked' => true],
                                'Classificação' => ['hint' => 'Definição pendente — confirmar com o cliente.', 'pending' => true],
                                'Situação ABAC' => ['hint' => 'Definição pendente — confirmar com o cliente.', 'pending' => true],
                                'Classificação Administradora' => ['hint' => 'Definição pendente — confirmar com o cliente.', 'pending' => true],
                            ];

                            $fields = [
                                'Código Omie' => $client->cod_omie,
                                'Nome Fantasia' => $client->nome_fantasia,
                                'Nome / Razão Social' => $client->nome,
                                'Classificação' => $client->classificacao,
                                'Categoria' => $client->categoria,
                                'CPF/CNPJ' => $client->cpf_cnpj,
                                'Inscrição Estadual' => $client->inscri_estadual,
                                'Inscrição Municipal' => $client->inscri_municipal,
                                'Tipo Cliente' => $client->tipo_cliente,
                                'Status' => $client->status,
                                'Telefone' => $client->telefone,
                                'Celular Admin' => $client->celular_admin,
                                'E-mail Admin' => $client->email_admin,
                                'Contato Admin' => $client->contato_name_admin,
                                'Regional' => $client->regional,
                                'Associado' => $client->associado,
                                'Situação ABAC' => $client->situacao_abac,
                                'Data BACEN' => $client->dt_bacen ? \Carbon\Carbon::parse($client->dt_bacen)->format('d/m/Y') : null,
                                'Classificação Administradora' => $client->classificao_administradora,
                                'E-mail CONAC' => $client->email_conac,
                                'Segmentos' => $client->segmentos,
                                'Área de Atuação' => $client->area_atuacao,
                                'E-mail 2' => $client->email_2,
                                'E-mail 3' => $client->email_3,
                                'E-mail 4' => $client->email_4,
                                'E-mail 5' => $client->email_5,
                                'E-mail 6' => $client->email_6,
                                'E-mail 7' => $client->email_7,
                            ];
                        @endphp

                        @foreach ($fields as $label => $value)
                            @php $hint = $fieldHints[$label] ?? null; @endphp
                            <div class="{{ in_array($label, ['Nome / Razão Social', 'E-mail Admin', 'Contato Admin', 'Segmentos', 'Área de Atuação']) ? 'md:col-span-2' : '' }}">
                                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-slate-700">
                                    <span>{{ $label }}</span>
                                    @if($hint)
                                        <span class="text-xs text-slate-400" title="{{ $hint['hint'] }}">ⓘ</span>
                                    @endif
                                    @if($hint['locked'] ?? false)
                                        <span class="ml-auto rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-600">🔒 bloqueado</span>
                                    @endif
                                </label>
                                <div class="rounded-xl border {{ ($hint['pending'] ?? false) && empty($value) ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} px-4 py-3 text-sm text-slate-800">
                                    {{ $value ?: (($hint['pending'] ?? false) ? '(definir com o cliente)' : '-') }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Histórico ABAC --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <p class="text-xs uppercase text-slate-500">Associado ABAC</p>
                                <p class="text-sm font-semibold">{{ $client->associado_abac ? 'Sim' : 'Não' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Nº filiação atual</p>
                                <p class="text-sm font-semibold">{{ $client->num_filiacao_abac ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Data filiação</p>
                                <p class="text-sm font-semibold">{{ $client->dt_filiacao_abac?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Data desfiliação</p>
                                <p class="text-sm font-semibold">{{ $client->dt_desfiliacao_abac?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                        </div>
                        @include('clients.partials._filiacoes', ['tipo' => 'abac'])
                    </div>

                    {{-- Histórico SINAC --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <p class="text-xs uppercase text-slate-500">Associado SINAC</p>
                                <p class="text-sm font-semibold">{{ $client->associado_sinac ? 'Sim' : 'Não' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Nº filiação atual</p>
                                <p class="text-sm font-semibold">{{ $client->num_filiacao_sinac ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Data filiação</p>
                                <p class="text-sm font-semibold">{{ $client->dt_filiacao_sinac?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-slate-500">Data desfiliação</p>
                                <p class="text-sm font-semibold">{{ $client->dt_desfiliacao_sinac?->format('d/m/Y') ?: '-' }}</p>
                            </div>
                        </div>
                        @include('clients.partials._filiacoes', ['tipo' => 'sinac'])
                    </div>

                    {{-- Redes sociais --}}
                    <div x-data="{ openRede: false }" class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-700">Redes sociais</h3>
                            @can('clients.edit')
                                <button @click="openRede = true"
                                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                    + Adicionar rede
                                </button>
                            @endcan
                        </div>

                        @if ($client->redesSociais->isEmpty())
                            <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhuma rede social cadastrada.</p>
                        @else
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                @foreach ($client->redesSociais as $rede)
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                                        <div class="min-w-0 flex-1">
                                            <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">{{ \App\Models\ClientRedeSocial::TIPOS[$rede->tipo] }}</span>
                                            @if ($rede->rotulo)
                                                <span class="text-xs text-slate-500">· {{ $rede->rotulo }}</span>
                                            @endif
                                            <a href="{{ $rede->url }}" target="_blank" rel="noopener"
                                               class="ml-2 break-all text-blue-600 hover:underline">{{ $rede->url }}</a>
                                        </div>
                                        @can('clients.edit')
                                            <form method="POST" action="{{ route('clients.redes.destroy', [$client, $rede]) }}">
                                                @csrf @method('DELETE')
                                                <button onclick="return confirm('Remover?')"
                                                        class="rounded-lg border border-red-200 px-2 py-0.5 text-xs text-red-600 hover:bg-red-50">×</button>
                                            </form>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @can('clients.edit')
                            <div x-show="openRede" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="openRede = false" class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
                                        <form method="POST" action="{{ route('clients.redes.store', $client) }}">
                                            @csrf
                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Adicionar rede social</h3>
                                            </div>
                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Tipo *</label>
                                                    <select name="tipo" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                        @foreach (\App\Models\ClientRedeSocial::TIPOS as $key => $label)
                                                            <option value="{{ $key }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Rótulo</label>
                                                    <input type="text" name="rotulo" placeholder="Ex.: Perfil oficial" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">URL *</label>
                                                    <input type="url" name="url" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                                <button type="button" @click="openRede = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                                                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endcan
                    </div>

                    {{-- Modal: Opcionais (datas, motivos, observações) --}}
                    @can('clients.edit')
                        <div x-show="openOpcionais" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="openOpcionais = false" class="relative my-8 w-full max-w-3xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="{{ route('clients.update', $client) }}">
                                        @csrf @method('PUT')
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Opcionais</h3>
                                            <p class="text-xs text-slate-500">Filiações, datas da empresa e observações. Para outros números/datas anteriores, use o botão "+ Adicionar filiação anterior" abaixo.</p>
                                        </div>

                                        <div class="space-y-5 p-6">
                                            {{-- ABAC --}}
                                            <div class="rounded-xl border border-slate-200 p-4">
                                                <h4 class="mb-3 text-sm font-semibold text-slate-700">Filiação ABAC</h4>
                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Nº filiação</label>
                                                        <input type="text" name="num_filiacao_abac" value="{{ $client->num_filiacao_abac }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de filiação</label>
                                                        <input type="date" name="dt_filiacao_abac" value="{{ $client->dt_filiacao_abac?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de desfiliação</label>
                                                        <input type="date" name="dt_desfiliacao_abac" value="{{ $client->dt_desfiliacao_abac?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Motivo da desfiliação</label>
                                                        <input type="text" name="motivo_desfiliacao_abac" value="{{ $client->motivo_desfiliacao_abac }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- SINAC --}}
                                            <div class="rounded-xl border border-slate-200 p-4">
                                                <h4 class="mb-3 text-sm font-semibold text-slate-700">Filiação SINAC</h4>
                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Nº filiação</label>
                                                        <input type="text" name="num_filiacao_sinac" value="{{ $client->num_filiacao_sinac }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de filiação</label>
                                                        <input type="date" name="dt_filiacao_sinac" value="{{ $client->dt_filiacao_sinac?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de desfiliação</label>
                                                        <input type="date" name="dt_desfiliacao_sinac" value="{{ $client->dt_desfiliacao_sinac?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Motivo da desfiliação</label>
                                                        <input type="text" name="motivo_desfiliacao_sinac" value="{{ $client->motivo_desfiliacao_sinac }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Datas da empresa --}}
                                            <div class="rounded-xl border border-slate-200 p-4">
                                                <h4 class="mb-3 text-sm font-semibold text-slate-700">Datas da empresa</h4>
                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de abertura</label>
                                                        <input type="date" name="dt_abertura_empresa" value="{{ $client->dt_abertura_empresa?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-medium text-slate-600">Data de comemoração de aniversário</label>
                                                        <input type="date" name="dt_aniversario_empresa" value="{{ $client->dt_aniversario_empresa?->format('Y-m-d') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Observações --}}
                                            <div class="rounded-xl border border-slate-200 p-4">
                                                <label class="mb-1 block text-sm font-semibold text-slate-700">Observações</label>
                                                <textarea name="obs" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">{{ $client->obs }}</textarea>
                                            </div>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="openOpcionais = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar</button>
                                            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            @endif

            @if ($activeTab === 'enderecos')
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Endereços</h2>
                            <p class="text-sm text-slate-500">Cadastros vinculados a este cliente.</p>
                        </div>

                        @can('clients.edit')
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar endereço
                            </button>
                        @endcan
                    </div>

                    <form method="GET" action="{{ route('clients.show', $client) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="enderecos">
                        <div class="md:col-span-2">
                            <input type="text" name="address_search" value="{{ request('address_search') }}"
                                   placeholder="Buscar por rua, bairro, município, estado, CEP..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'enderecos']) }}"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">CEP</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Rua</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Número</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Bairro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Município/UF</th>
                                        @can('clients.edit')
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($addresses as $address)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="rounded-full {{ $address->tipo === 'principal' ? 'bg-blue-100 text-blue-700' : ($address->tipo === 'secundario' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }} px-2 py-0.5 text-xs">
                                                    {{ \App\Models\ClientEndereco::TIPOS[$address->tipo] ?? 'Outro' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $address->cep ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $address->rua ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $address->numero ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $address->bairro ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $address->municipio ?: '-' }} / {{ $address->estado ?: '-' }}</td>
                                            @can('clients.edit')
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'address-{{ $address->id }}'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="{{ route('clients.addresses.destroy', [$client, $address]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este endereço?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum endereço encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ $addresses->links() }}

                    @can('clients.edit')
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="{{ route('clients.addresses.store', $client) }}">
                                        @csrf
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar endereço</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Tipo do endereço</label>
                                                <select name="tipo" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    @foreach (\App\Models\ClientEndereco::TIPOS as $key => $label)
                                                        <option value="{{ $key }}" {{ ($address->tipo ?? 'outro') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">CEP</label>
                                                <input type="text" name="cep" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Rua</label>
                                                <input type="text" name="rua" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                                                <input type="text" name="numero" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                                                <input type="text" name="complemento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Bairro</label>
                                                <input type="text" name="bairro" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">País</label>
                                                <input type="text" name="pais" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
                                                <input type="text" name="estado" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Código IBGE</label>
                                                <input type="text" name="cod_ibge" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Município</label>
                                                <input type="text" name="municipio" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @foreach($addresses as $address)
                            <div x-show="editOpen === 'address-{{ $address->id }}'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="{{ route('clients.addresses.update', [$client, $address]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar endereço</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">CEP</label>
                                                    <input type="text" name="cep" value="{{ $address->cep }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Rua</label>
                                                    <input type="text" name="rua" value="{{ $address->rua }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número</label>
                                                    <input type="text" name="numero" value="{{ $address->numero }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                                                    <input type="text" name="complemento" value="{{ $address->complemento }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Bairro</label>
                                                    <input type="text" name="bairro" value="{{ $address->bairro }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">País</label>
                                                    <input type="text" name="pais" value="{{ $address->pais }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
                                                    <input type="text" name="estado" value="{{ $address->estado }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Código IBGE</label>
                                                    <input type="text" name="cod_ibge" value="{{ $address->cod_ibge }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Município</label>
                                                    <input type="text" name="municipio" value="{{ $address->municipio }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endcan
                </div>
            @endif

            @if ($activeTab === 'contatos')
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Contatos</h2>
                            <p class="text-sm text-slate-500">Pessoas vinculadas a este cliente.</p>
                        </div>

                        @can('clients.edit')
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar contato
                            </button>
                        @endcan
                    </div>

                    <form method="GET" action="{{ route('clients.show', $client) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="contatos">
                        <div class="md:col-span-2">
                            <input type="text" name="contact_search" value="{{ request('contact_search') }}"
                                   placeholder="Buscar por nome, e-mail, telefone, função..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'contatos']) }}"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nome</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Função</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">E-mail</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Telefone</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Departamento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Flags</th>
                                        @can('clients.edit')
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($contacts as $contact)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $contact->nome ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $contact->funcao ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $contact->email ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $contact->telefone ?: '-' }}
                                                @if($contact->telefone_2)
                                                    <div class="text-xs text-slate-500">{{ $contact->telefone_2 }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $contact->departamento ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <div class="flex flex-wrap gap-1">
                                                    @if($contact->representante_legal)
                                                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Rep. Legal</span>
                                                    @endif
                                                    @if($contact->comite)
                                                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Comitê</span>
                                                    @endif
                                                    @if($contact->unlock_whatsApp)
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">WhatsApp</span>
                                                    @endif
                                                </div>
                                            </td>
                                            @can('clients.edit')
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'contact-{{ $contact->id }}'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="{{ route('clients.contacts.destroy', [$client, $contact]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este contato?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum contato encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ $contacts->links() }}

                    @can('clients.edit')
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-5xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="{{ route('clients.contacts.store', $client) }}">
                                        @csrf
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar contato</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Nome</label>
                                                <input type="text" name="nome" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Função</label>
                                                <input type="text" name="funcao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Nascimento</label>
                                                <input type="date" name="dt_nascimento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                                <input type="email" name="email" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 2</label>
                                                <input type="email" name="email_2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                                <input type="text" name="telefone" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Ramal</label>
                                                <input type="text" name="ramal" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Celular</label>
                                                <input type="text" name="celular" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone 2</label>
                                                <input type="text" name="telefone_2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Departamento</label>
                                                <input type="text" name="departamento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div class="xl:col-span-4">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Observação</label>
                                                <textarea name="obs" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                                            </div>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="representante_legal" value="1" class="rounded border-slate-300">
                                                Representante legal
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="comite" value="1" class="rounded border-slate-300">
                                                Comitê
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox" name="unlock_whatsApp" value="1" class="rounded border-slate-300">
                                                Liberar WhatsApp
                                            </label>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @foreach($contacts as $contact)
                            <div x-show="editOpen === 'contact-{{ $contact->id }}'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-5xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="{{ route('clients.contacts.update', [$client, $contact]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar contato</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nome</label>
                                                    <input type="text" name="nome" value="{{ $contact->nome }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Função</label>
                                                    <input type="text" name="funcao" value="{{ $contact->funcao }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Nascimento</label>
                                                    <input type="date" name="dt_nascimento" value="{{ $contact->dt_nascimento ? \Carbon\Carbon::parse($contact->dt_nascimento)->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                                    <input type="email" name="email" value="{{ $contact->email }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 2</label>
                                                    <input type="email" name="email_2" value="{{ $contact->email_2 }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                                    <input type="text" name="telefone" value="{{ $contact->telefone }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Ramal</label>
                                                    <input type="text" name="ramal" value="{{ $contact->ramal }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Celular</label>
                                                    <input type="text" name="celular" value="{{ $contact->celular }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Telefone 2</label>
                                                    <input type="text" name="telefone_2" value="{{ $contact->telefone_2 }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Departamento</label>
                                                    <input type="text" name="departamento" value="{{ $contact->departamento }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div class="xl:col-span-4">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Observação</label>
                                                    <textarea name="obs" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ $contact->obs }}</textarea>
                                                </div>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="representante_legal" value="1" @checked($contact->representante_legal) class="rounded border-slate-300">
                                                    Representante legal
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="comite" value="1" @checked($contact->comite) class="rounded border-slate-300">
                                                    Comitê
                                                </label>
                                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                                    <input type="checkbox" name="unlock_whatsApp" value="1" @checked($contact->unlock_whatsApp) class="rounded border-slate-300">
                                                    Liberar WhatsApp
                                                </label>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endcan
                </div>
            @endif

            @if ($activeTab === 'opcionais')
                <div x-data="{ createOpen: false, editOpen: null }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Opcionais</h2>
                            <p class="text-sm text-slate-500">Registros opcionais vinculados a este cliente.</p>
                        </div>

                        @can('clients.edit')
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar opcional
                            </button>
                        @endcan
                    </div>

                    <form method="GET" action="{{ route('clients.show', $client) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="opcionais">
                        <div class="md:col-span-2">
                            <input type="text" name="opcional_search" value="{{ request('opcional_search') }}"
                                   placeholder="Buscar por site, número ABAC ou SINAC..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'opcionais']) }}"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Site</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Início Atividade</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nº ABAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Fim ABAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Nº SINAC</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Fim SINAC</th>
                                        @can('clients.edit')
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($opcionais as $opcional)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->site ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->inicio_atv ? \Carbon\Carbon::parse($opcional->inicio_atv)->format('d/m/Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->num_abac ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->dt_f_abac ? \Carbon\Carbon::parse($opcional->dt_f_abac)->format('d/m/Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->num_sinac ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $opcional->dt_f_sinac ? \Carbon\Carbon::parse($opcional->dt_f_sinac)->format('d/m/Y') : '-' }}</td>
                                            @can('clients.edit')
                                                <td class="px-4 py-3 text-right">
                                                    <div class="inline-flex gap-2">
                                                        <button type="button"
                                                                @click="editOpen = 'opcional-{{ $opcional->id }}'"
                                                                class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
                                                            Editar
                                                        </button>

                                                        <form method="POST" action="{{ route('clients.opcionais.destroy', [$client, $opcional]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este registro opcional?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum registro opcional encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ $opcionais->links() }}

                    @can('clients.edit')
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="{{ route('clients.opcionais.store', $client) }}">
                                        @csrf
                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar opcional</h3>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                                            <div class="xl:col-span-3">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Site</label>
                                                <input type="text" name="site" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Início Atividade</label>
                                                <input type="date" name="inicio_atv" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número ABAC</label>
                                                <input type="text" name="num_abac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Fim ABAC</label>
                                                <input type="date" name="dt_f_abac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Número SINAC</label>
                                                <input type="text" name="num_sinac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">Fim SINAC</label>
                                                <input type="date" name="dt_f_sinac" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            </div>
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        @foreach($opcionais as $opcional)
                            <div x-show="editOpen === 'opcional-{{ $opcional->id }}'" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                                <div class="flex min-h-full items-center justify-center p-4">
                                    <div @click.away="editOpen = null" class="relative my-8 w-full max-w-4xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                        <form method="POST" action="{{ route('clients.opcionais.update', [$client, $opcional]) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="border-b border-slate-200 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-slate-900">Editar opcional</h3>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2 xl:grid-cols-3">
                                                <div class="xl:col-span-3">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Site</label>
                                                    <input type="text" name="site" value="{{ $opcional->site }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Início Atividade</label>
                                                    <input type="date" name="inicio_atv" value="{{ $opcional->inicio_atv ? \Carbon\Carbon::parse($opcional->inicio_atv)->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número ABAC</label>
                                                    <input type="text" name="num_abac" value="{{ $opcional->num_abac }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Fim ABAC</label>
                                                    <input type="date" name="dt_f_abac" value="{{ $opcional->dt_f_abac ? \Carbon\Carbon::parse($opcional->dt_f_abac)->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Número SINAC</label>
                                                    <input type="text" name="num_sinac" value="{{ $opcional->num_sinac }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">Fim SINAC</label>
                                                    <input type="date" name="dt_f_sinac" value="{{ $opcional->dt_f_sinac ? \Carbon\Carbon::parse($opcional->dt_f_sinac)->format('Y-m-d') : '' }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                </div>
                                            </div>

                                            <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                                <button type="button" @click="editOpen = null"
                                                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                    Cancelar
                                                </button>
                                                <button type="submit"
                                                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                    Atualizar
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endcan
                </div>
            @endif

            @if ($activeTab === 'ged' && auth()->user()->can('documents.view'))
                <div x-data="{ createOpen: false }" class="space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                GED · {{ $gedCategory ? \App\Models\Document::CATEGORIES[$gedCategory] : 'Todos os documentos' }}
                            </h2>
                            <p class="text-sm text-slate-500">
                                {{ $gedCategory ? 'Documentos desta categoria.' : 'Documentos vinculados a este cliente. Use o menu lateral para filtrar por categoria.' }}
                            </p>
                        </div>

                        @can('documents.create')
                            <button type="button"
                                    @click="createOpen = true"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Adicionar documentos
                            </button>
                        @endcan
                    </div>

                    <form method="GET" action="{{ route('clients.show', $client) }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <input type="hidden" name="tab" value="ged">
                        @if($gedCategory)
                            <input type="hidden" name="subtab" value="{{ $gedCategory }}">
                        @endif
                        <div class="md:col-span-2">
                            <input type="text" name="document_search" value="{{ request('document_search') }}"
                                   placeholder="Buscar por título, arquivo, tipo ou descrição..."
                                   class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white">Buscar</button>
                        </div>
                        <div>
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'ged']) }}"
                               class="block w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-700">
                                Limpar
                            </a>
                        </div>
                    </form>

                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Título</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Arquivo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Categoria</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Vencimento</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Enviado por</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse($documents as $document)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $document->title ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $document->original_name ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                    {{ \App\Models\Document::CATEGORIES[$document->category] ?? 'Demais documentos' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $document->type ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $document->expiration_date ? \Carbon\Carbon::parse($document->expiration_date)->format('d/m/Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">{{ $document->uploader?->name ?: '-' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex gap-2">
                                                    <a href="{{ route('clients.documents.download', [$client, $document]) }}"
                                                       class="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50">
                                                        Download
                                                    </a>

                                                    @can('documents.delete')
                                                        <form method="POST" action="{{ route('clients.documents.destroy', [$client, $document]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    onclick="return confirm('Deseja excluir este documento?')"
                                                                    class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Nenhum documento encontrado.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{ $documents->links() }}

                    @can('documents.create')
                        <div x-show="createOpen" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                            <div class="flex min-h-full items-center justify-center p-4">
                                <div @click.away="createOpen = false" class="relative my-8 w-full max-w-6xl rounded-2xl bg-white shadow-2xl max-h-[90vh] overflow-y-auto">
                                    <form method="POST" action="{{ route('clients.documents.store', $client) }}" enctype="multipart/form-data">
                                        @csrf

                                        <div class="border-b border-slate-200 px-6 py-4">
                                            <h3 class="text-lg font-semibold text-slate-900">Adicionar documentos</h3>
                                            <p class="mt-1 text-sm text-slate-500">Você pode enviar até 5 arquivos por vez.</p>
                                        </div>

                                        <div class="space-y-4 p-6">
                                            @for($i = 0; $i < 5; $i++)
                                                <div class="grid grid-cols-1 gap-4 rounded-2xl border border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-6">
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Arquivo</label>
                                                        <input type="file" name="files[]" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Título</label>
                                                        <input type="text" name="title[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Categoria</label>
                                                        <select name="category[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                            @foreach(\App\Models\Document::CATEGORIES as $catKey => $catLabel)
                                                                <option value="{{ $catKey }}" {{ $gedCategory === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo</label>
                                                        <input type="text" name="type[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Descrição</label>
                                                        <input type="text" name="description[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium text-slate-700">Vencimento</label>
                                                        <input type="date" name="expiration_date[]" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>

                                        <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-200 bg-white px-6 py-4">
                                            <button type="button" @click="createOpen = false"
                                                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">
                                                Cancelar
                                            </button>
                                            <button type="submit"
                                                    class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                Salvar documentos
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            @endif

            @if ($activeTab === 'financeiro')
                @include('clients.partials._financeiro')
            @endif

            @if ($activeTab === 'juridico')
                @include('clients.partials._juridico')
            @endif

            @if ($activeTab === 'secretaria')
                @include('clients.partials._secretaria')
            @endif

            @if ($activeTab === 'cadastro')
                @include('clients.partials._cadastro')
            @endif

            @if ($activeTab === 'tags')
                @include('clients.partials._tags')
            @endif

            @if ($activeTab === 'uso_interno')
                @include('clients.partials._uso_interno')
            @endif
        </div>
    </div>
</div>
@endsection
