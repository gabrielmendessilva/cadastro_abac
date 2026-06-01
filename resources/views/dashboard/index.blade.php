@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Usuários</p>
        <h3 class="text-4xl font-bold mt-2">{{ $stats['users'] }}</h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Clientes</p>
        <h3 class="text-4xl font-bold mt-2">{{ $stats['clients'] }}</h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Documentos</p>
        <h3 class="text-4xl font-bold mt-2">{{ $stats['documents'] }}</h3>
    </div>
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-slate-500 text-sm">Clientes ativos</p>
        <h3 class="text-4xl font-bold mt-2">{{ $stats['active_clients'] }}</h3>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Últimos clientes</h3>
            @can('clients.create')
                <a href="{{ route('clients.create') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Novo cliente</a>
            @endcan
        </div>
        <div class="space-y-3">
            @forelse($latestClients as $client)
                <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'geral']) }}"
                   class="block rounded-2xl border border-slate-200 p-4 flex justify-between items-center hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $client->nome ?: $client->nome_fantasia ?: '—' }}</p>
                        <p class="text-sm text-slate-500">{{ $client->cpf_cnpj ?: '-' }}</p>
                    </div>
                    <span class="text-sm shrink-0 ml-3 {{ $client->status ? 'text-emerald-600' : 'text-slate-500' }}">{{ $client->status ? 'Ativo' : 'Inativo' }}</span>
                </a>
            @empty
                <p class="text-slate-500">Nenhum cliente cadastrado.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Últimos documentos</h3>
            @can('documents.create')
                <a href="{{ route('documents.create') }}" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Novo documento</a>
            @endcan
        </div>
        <div class="space-y-3">
            @forelse($latestDocuments as $document)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <p class="font-medium">{{ $document->title }}</p>
                    <p class="text-sm text-slate-500">Cliente: {{ $document->client->nome ?? '-' }}</p>
                </div>
            @empty
                <p class="text-slate-500">Nenhum documento enviado.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
