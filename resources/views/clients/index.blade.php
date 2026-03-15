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

    <form method="GET" class="mb-6 grid gap-3 md:grid-cols-5">
        <input type="text" name="search" value="{{ request('search') }}" class="rounded-2xl border border-slate-300 px-4 py-3" placeholder="Nome, CPF/CNPJ ou e-mail">
        <input type="text" name="city" value="{{ request('city') }}" class="rounded-2xl border border-slate-300 px-4 py-3" placeholder="Cidade">
        <input type="text" name="state" value="{{ request('state') }}" maxlength="2" class="rounded-2xl border border-slate-300 px-4 py-3 uppercase" placeholder="UF">
        <select name="status" class="rounded-2xl border border-slate-300 px-4 py-3">
            <option value="">Status</option>
            <option value="1" @selected(request('status') === '1')>Ativo</option>
            <option value="0" @selected(request('status') === '0')>Inativo</option>
        </select>
        <div class="flex gap-3">
            <button class="w-full rounded-2xl bg-slate-900 px-5 py-3 text-white">Buscar</button>
            <a href="{{ route('clients.index') }}" class="rounded-2xl border px-5 py-3">Limpar</a>
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
                    <tr class="border-b border-slate-100">
                        <td class="py-4 pr-4">
                            <div class="font-medium">{{ $client->name }}</div>
                            <div class="text-xs text-slate-500">{{ $client->email ?: '-' }}</div>
                        </td>
                        <td class="py-4 pr-4">{{ $client->document }}</td>
                        <td class="py-4 pr-4">{{ trim(($client->city ?: '-') . ' / ' . ($client->state ?: '-')) }}</td>
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
                        <td colspan="6" class="py-6 text-center text-slate-500">Nenhum cliente encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $clients->links() }}</div>
</div>
@endsection
