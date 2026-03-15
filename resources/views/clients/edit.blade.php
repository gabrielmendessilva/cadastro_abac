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

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-slate-900">Dados gerais</h2>
                    <p class="text-sm text-slate-500">Atualize as informações principais do cliente.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Código Omie</label>
                        <input type="text" name="cod_omie" value="{{ old('cod_omie', $client->cod_omie) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome Fantasia</label>
                        <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $client->nome_fantasia) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome / Razão Social</label>
                        <input type="text" name="nome" value="{{ old('nome', $client->nome) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Classificação</label>
                        <input type="text" name="classificacao" value="{{ old('classificacao', $client->classificacao) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Categoria</label>
                        <input type="text" name="categoria" value="{{ old('categoria', $client->categoria) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">CPF/CNPJ</label>
                        <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $client->cpf_cnpj) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Inscrição Estadual</label>
                        <input type="text" name="inscri_estadual" value="{{ old('inscri_estadual', $client->inscri_estadual) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Inscrição Municipal</label>
                        <input type="text" name="inscri_municipal" value="{{ old('inscri_municipal', $client->inscri_municipal) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo Cliente</label>
                        <input type="text" name="tipo_cliente" value="{{ old('tipo_cliente', $client->tipo_cliente) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                        <input type="text" name="status" value="{{ old('status', $client->status) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                        <input type="text" name="telefone" value="{{ old('telefone', $client->telefone) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Celular Admin</label>
                        <input type="text" name="celular_admin" value="{{ old('celular_admin', $client->celular_admin) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail Admin</label>
                        <input type="text" name="email_admin" value="{{ old('email_admin', $client->email_admin) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Contato Admin</label>
                        <input type="text" name="contato_name_admin" value="{{ old('contato_name_admin', $client->contato_name_admin) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Regional</label>
                        <input type="text" name="regional" value="{{ old('regional', $client->regional) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Associado</label>
                        <input type="text" name="associado" value="{{ old('associado', $client->associado) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Situação ABAC</label>
                        <input type="text" name="situacao_abac" value="{{ old('situacao_abac', $client->situacao_abac) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Data BACEN</label>
                        <input type="date" name="dt_bacen"
                            value="{{ old('dt_bacen', $client->dt_bacen ? \Carbon\Carbon::parse($client->dt_bacen)->format('Y-m-d') : '') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Classificação Administradora</label>
                        <input type="text" name="classificao_administradora" value="{{ old('classificao_administradora', $client->classificao_administradora) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail CONAC</label>
                        <input type="text" name="email_conac" value="{{ old('email_conac', $client->email_conac) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Segmentos</label>
                        <input type="text" name="segmentos" value="{{ old('segmentos', $client->segmentos) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2 xl:col-span-4">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Área de Atuação</label>
                        <input type="text" name="area_atuacao" value="{{ old('area_atuacao', $client->area_atuacao) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 2</label>
                        <input type="text" name="email_2" value="{{ old('email_2', $client->email_2) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 3</label>
                        <input type="text" name="email_3" value="{{ old('email_3', $client->email_3) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 4</label>
                        <input type="text" name="email_4" value="{{ old('email_4', $client->email_4) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 5</label>
                        <input type="text" name="email_5" value="{{ old('email_5', $client->email_5) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 6</label>
                        <input type="text" name="email_6" value="{{ old('email_6', $client->email_6) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail 7</label>
                        <input type="text" name="email_7" value="{{ old('email_7', $client->email_7) }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observação 1</label>
                        <textarea name="obs" rows="4"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs', $client->obs) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Observação 2</label>
                        <textarea name="obs_2" rows="4"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_2', $client->obs_2) }}</textarea>
                    </div>
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
