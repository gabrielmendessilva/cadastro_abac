@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editar cliente</h1>
                <p class="text-sm text-slate-500">{{ $client->nome }}</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'geral']) }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Voltar
                </a>
            </div>
        </div>

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

        <form method="POST" action="{{ route('clients.update', $client) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Identificação --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" x-data="{ omieEditavel: false }">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Identificação</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 flex items-center justify-between text-sm font-medium text-slate-700">
                            <span>Código Omie <span class="ml-1 text-xs text-slate-400" title="Bloqueado para evitar alterações acidentais.">ⓘ</span></span>
                            <button type="button" @click="omieEditavel = !omieEditavel" class="text-xs font-medium text-blue-600 hover:underline" x-text="omieEditavel ? 'Cancelar' : 'Alterar'"></button>
                        </label>
                        <input type="text" name="cod_omie" value="{{ old('cod_omie', $client->cod_omie) }}"
                            :readonly="!omieEditavel"
                            :class="omieEditavel ? 'border-amber-400 bg-white' : 'border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed'"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome Fantasia</label>
                        <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $client->nome_fantasia) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome / Razão Social</label>
                        <input type="text" name="nome" value="{{ old('nome', $client->nome) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome Comercial</label>
                        <input type="text" name="nome_comercial" value="{{ old('nome_comercial', $client->nome_comercial) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="possui_outro_nome" value="1" @checked(old('possui_outro_nome', $client->possui_outro_nome)) class="rounded">
                        Possui outro nome?
                    </label>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Outros nomes</label>
                        <input type="text" name="outros_nomes" value="{{ old('outros_nomes', $client->outros_nomes) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Categoria</label>
                        <input type="text" name="categoria" value="{{ old('categoria', $client->categoria) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">CNPJ / CPF</label>
                        <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $client->cpf_cnpj) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">CPF (PF)</label>
                        <input type="text" name="cpf" value="{{ old('cpf', $client->cpf) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">RG</label>
                        <input type="text" name="rg" value="{{ old('rg', $client->rg) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data de nascimento</label>
                        <input type="date" name="dt_nascimento" value="{{ old('dt_nascimento', $client->dt_nascimento?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Regional</label>
                        <input type="text" name="regional" value="{{ old('regional', $client->regional) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Inscrição Estadual</label>
                        <input type="text" name="inscri_estadual" value="{{ old('inscri_estadual', $client->inscri_estadual) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Inscrição Municipal</label>
                        <input type="text" name="inscri_municipal" value="{{ old('inscri_municipal', $client->inscri_municipal) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Status da empresa</label>
                        <input type="text" name="status_empresa" value="{{ old('status_empresa', $client->status_empresa) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="autenticacao_whatsapp" value="1" @checked(old('autenticacao_whatsapp', $client->autenticacao_whatsapp)) class="rounded">
                        Autenticação WhatsApp
                    </label>
                </div>
            </div>

            {{-- Filiação ABAC --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Filiação ABAC</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="associado_abac" value="1" @checked(old('associado_abac', $client->associado_abac)) class="rounded">
                        Associado ABAC
                    </label>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data filiação atual</label>
                        <input type="date" name="dt_filiacao_abac" value="{{ old('dt_filiacao_abac', $client->dt_filiacao_abac?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nº filiação</label>
                        <input type="text" name="num_filiacao_abac" value="{{ old('num_filiacao_abac', $client->num_filiacao_abac) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data desfiliação</label>
                        <input type="date" name="dt_desfiliacao_abac" value="{{ old('dt_desfiliacao_abac', $client->dt_desfiliacao_abac?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Motivo da desfiliação</label>
                        <input type="text" name="motivo_desfiliacao_abac" value="{{ old('motivo_desfiliacao_abac', $client->motivo_desfiliacao_abac) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observações ABAC</label>
                        <textarea name="obs_abac" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_abac', $client->obs_abac) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Situação ABAC <span class="ml-1 text-xs text-slate-400" title="Definição pendente — confirmar com o cliente.">ⓘ</span>
                        </label>
                        <input type="text" name="situacao_abac" value="{{ old('situacao_abac', $client->situacao_abac) }}" placeholder="(definir com o cliente)" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Classificação <span class="ml-1 text-xs text-slate-400" title="Definição pendente — confirmar com o cliente.">ⓘ</span>
                        </label>
                        <input type="text" name="classificacao" value="{{ old('classificacao', $client->classificacao) }}" placeholder="(definir com o cliente)" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Classificação Administradora <span class="ml-1 text-xs text-slate-400" title="Definição pendente — confirmar com o cliente.">ⓘ</span>
                        </label>
                        <input type="text" name="classificao_administradora" value="{{ old('classificao_administradora', $client->classificao_administradora) }}" placeholder="(definir com o cliente)" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Associado</label>
                        <input type="text" name="associado" value="{{ old('associado', $client->associado) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                </div>
            </div>

            {{-- Filiação SINAC --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Filiação SINAC</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="associado_sinac" value="1" @checked(old('associado_sinac', $client->associado_sinac)) class="rounded">
                        Associado SINAC
                    </label>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data filiação atual</label>
                        <input type="date" name="dt_filiacao_sinac" value="{{ old('dt_filiacao_sinac', $client->dt_filiacao_sinac?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nº filiação</label>
                        <input type="text" name="num_filiacao_sinac" value="{{ old('num_filiacao_sinac', $client->num_filiacao_sinac) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data desfiliação</label>
                        <input type="date" name="dt_desfiliacao_sinac" value="{{ old('dt_desfiliacao_sinac', $client->dt_desfiliacao_sinac?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Motivo da desfiliação</label>
                        <input type="text" name="motivo_desfiliacao_sinac" value="{{ old('motivo_desfiliacao_sinac', $client->motivo_desfiliacao_sinac) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observações SINAC</label>
                        <textarea name="obs_sinac" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_sinac', $client->obs_sinac) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Datas da empresa --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Datas da empresa</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Abertura</label>
                        <input type="date" name="dt_abertura_empresa" value="{{ old('dt_abertura_empresa', $client->dt_abertura_empresa?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Aniversário</label>
                        <input type="date" name="dt_aniversario_empresa" value="{{ old('dt_aniversario_empresa', $client->dt_aniversario_empresa?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Pedido p/ administrar consórcio</label>
                        <input type="date" name="dt_pedido_consorcio" value="{{ old('dt_pedido_consorcio', $client->dt_pedido_consorcio?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Autorização p/ administrar consórcio</label>
                        <input type="date" name="dt_autorizacao_consorcio" value="{{ old('dt_autorizacao_consorcio', $client->dt_autorizacao_consorcio?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data BACEN</label>
                        <input type="date" name="dt_bacen" value="{{ old('dt_bacen', $client->dt_bacen ? \Carbon\Carbon::parse($client->dt_bacen)->format('Y-m-d') : '') }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                </div>
            </div>

            {{-- Contatos da empresa --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Contatos da empresa</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Responsável pela empresa</label>
                        <input type="text" name="responsavel_empresa" value="{{ old('responsavel_empresa', $client->responsavel_empresa) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone', $client->telefone) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Celular Admin</label>
                        <input type="text" name="celular_admin" value="{{ old('celular_admin', $client->celular_admin) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail da empresa</label>
                        <input type="text" name="email_admin" value="{{ old('email_admin', $client->email_admin) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contato Admin</label>
                        <input type="text" name="contato_name_admin" value="{{ old('contato_name_admin', $client->contato_name_admin) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail ouvidoria</label>
                        <input type="text" name="email_ouvidoria" value="{{ old('email_ouvidoria', $client->email_ouvidoria) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Telefone ouvidoria</label>
                        <input type="text" name="telefone_ouvidoria" value="{{ old('telefone_ouvidoria', $client->telefone_ouvidoria) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail CONAC</label>
                        <input type="text" name="email_conac" value="{{ old('email_conac', $client->email_conac) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                </div>
            </div>

            {{-- FINANCEIRO --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Financeiro</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            E-mails para receber boletos de Contribuição Associativa
                            <span class="ml-1 text-xs text-slate-400">(um por linha)</span>
                        </label>
                        <textarea name="emails_boletos" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('emails_boletos', $client->emails_boletos) }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="possui_contrato_ativo" value="1" @checked(old('possui_contrato_ativo', $client->possui_contrato_ativo)) class="rounded">
                        Possui contrato ativo?
                    </label>
                </div>
                <p class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                    💡 Os contratos individuais (descrição, responsável, vencimento) são gerenciados na
                    <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'financeiro']) }}" class="font-medium underline">aba Financeiro</a>.
                </p>
            </div>

            {{-- JURÍDICO observações --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Jurídico — observações</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observações Jurídico</label>
                        <textarea name="obs_juridico" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_juridico', $client->obs_juridico) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observações SINAC (jurídico)</label>
                        <textarea name="obs_sinac_juridico" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_sinac_juridico', $client->obs_sinac_juridico) }}</textarea>
                    </div>
                </div>
                <p class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                    💡 Sócios/administradores e contatos jurídico/SINAC são gerenciados na
                    <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'juridico']) }}" class="font-medium underline">aba Jurídico</a>.
                </p>
            </div>

            {{-- SECRETARIA --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Secretaria</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Presidente atual</label>
                        <input type="text" name="presidente_atual" value="{{ old('presidente_atual', $client->presidente_atual) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="mandato_alerta" value="1" @checked(old('mandato_alerta', $client->mandato_alerta)) class="rounded">
                        Avisar quando mandato vencer
                    </label>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Início do mandato</label>
                        <input type="date" name="mandato_inicio" value="{{ old('mandato_inicio', $client->mandato_inicio?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Término do mandato</label>
                        <input type="date" name="mandato_termino" value="{{ old('mandato_termino', $client->mandato_termino?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail do presidente</label>
                        <input type="email" name="email_presidente" value="{{ old('email_presidente', $client->email_presidente) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail da secretaria / contato na empresa</label>
                        <input type="email" name="email_secretaria" value="{{ old('email_secretaria', $client->email_secretaria) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                </div>
            </div>

            {{-- CADASTRO --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Cadastro — informações da empresa</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Segmento</label>
                        <input type="text" name="segmento" value="{{ old('segmento', $client->segmento) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Área de Atuação</label>
                        <input type="text" name="area_atuacao" value="{{ old('area_atuacao', $client->area_atuacao) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Segmentos (texto livre — legacy)</label>
                        <input type="text" name="segmentos" value="{{ old('segmentos', $client->segmentos) }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observação (cadastro)</label>
                        <textarea name="obs_cadastro" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_cadastro', $client->obs_cadastro) }}</textarea>
                    </div>
                </div>
                <p class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                    💡 Departamentos/contatos e comitês são gerenciados nas abas
                    <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'contatos']) }}" class="font-medium underline">Contatos</a> e
                    <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'cadastro']) }}" class="font-medium underline">Cadastro</a>.
                </p>
            </div>

            {{-- Status / Outras observações --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Status e observações livres</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm md:col-span-2">
                        <input type="checkbox" name="status" value="1" @checked(old('status', $client->status)) class="rounded">
                        Cliente ativo no sistema
                    </label>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observação 1</label>
                        <textarea name="obs" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs', $client->obs) }}</textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observação 2</label>
                        <textarea name="obs_2" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_2', $client->obs_2) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Atalhos para recursos relacionais (não estão neste form) --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                <p class="font-semibold">📌 Recursos gerenciados nas abas do cliente</p>
                <p class="mt-1 text-xs">Os itens abaixo não fazem parte deste formulário porque permitem múltiplos registros (lista). Use o link para abrir a aba correspondente.</p>
                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @php
                        $atalhos = [
                            'enderecos' => ['📍', 'Endereços', 'Tipos: principal/secundário/outros'],
                            'contatos' => ['📇', 'Contatos da empresa', 'Departamentos com nome, função, e-mails, ramal, celular'],
                            'geral' => ['🌐', 'Redes sociais', 'Site, LinkedIn, Instagram, Facebook (popup na aba Geral)'],
                            'financeiro' => ['💰', 'Contratos', 'Adicionar contratos com vencimento'],
                            'juridico' => ['⚖️', 'Sócios e contatos jurídico', 'Sociedade, sócios, administradores'],
                            'cadastro' => ['👥', 'Comitês', 'Comitês em que o cliente participa'],
                            'ged' => ['📁', 'Documentos (GED)', 'Por categoria: ABAC, SINAC, BCB, Demais'],
                            'tags' => ['🏷️', 'Tags', 'Etiquetas para classificação'],
                            'opcionais' => ['➕', 'Filiações ABAC/SINAC (opcionais)', 'Site, números, datas'],
                        ];
                    @endphp
                    @foreach ($atalhos as $tab => [$icon, $title, $desc])
                        <a href="{{ route('clients.show', ['client' => $client, 'tab' => $tab]) }}"
                           class="flex items-start gap-2 rounded-xl border border-amber-200 bg-white px-3 py-2 text-amber-900 hover:bg-amber-100">
                            <span class="text-lg">{{ $icon }}</span>
                            <div>
                                <p class="font-medium">{{ $title }} →</p>
                                <p class="text-xs text-amber-700">{{ $desc }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'geral']) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
@endsection
