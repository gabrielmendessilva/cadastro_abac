@extends('layouts.app')

@section('title', 'Novo cliente')
@section('page-title', 'Novo cliente')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Novo cliente</h1>
                <p class="text-sm text-slate-500">Preencha os dados básicos. O restante do cadastro fica disponível após salvar.</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('clients.index') }}"
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

        <form method="POST" action="{{ route('clients.store') }}" class="space-y-6">
            @csrf

            {{-- Identificação --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Identificação</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Nome / Razão Social <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">CNPJ / CPF <span class="text-rose-600">*</span></label>
                        <input type="text" name="document" value="{{ old('document') }}" required
                            placeholder="00.000.000/0000-00"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Nome Fantasia</label>
                        <input type="text" name="fantasy_name" value="{{ old('fantasy_name') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Regional</label>
                        <select name="regional_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            <option value="">—</option>
                            @foreach ($regionais as $regional)
                                <option value="{{ $regional->id }}" @selected(old('regional_id') == $regional->id)>{{ $regional->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Contatos da empresa --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Contatos da empresa</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">E-mail da empresa</label>
                        <input type="text" name="email" value="{{ old('email') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Celular Admin</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Status</h2>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" name="status" value="1" @checked(old('status', true)) class="rounded">
                    Cliente ativo no sistema
                </label>
            </div>

            {{-- Aviso sobre o restante do cadastro --}}
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                <p class="font-semibold">📌 O cadastro completo é feito após salvar</p>
                <p class="mt-1 text-xs">
                    Endereços, contatos por departamento, sócios, comitês, contratos, documentos (GED) e demais dados
                    são gerenciados nas abas do cliente. Filiações ABAC/SINAC, datas e observações ficam na tela de edição.
                </p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('clients.index') }}"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Cancelar
                </a>
                <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Salvar cliente
                </button>
            </div>
        </form>
    </div>
@endsection
