@extends('layouts.app')
@section('title', 'GED / Documentos')
@section('page-title', 'GED / Documentos')

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold">Gestão eletrônica de documentos</h3>
            <p class="text-slate-500 text-sm">Documentos vinculados aos clientes.</p>
        </div>
        @can('documents.create')<a href="{{ route('documents.create') }}" class="rounded-2xl bg-indigo-600 px-5 py-3 text-white">Novo documento</a>@endcan
    </div>

    <form method="GET" class="mb-6">
        <div class="flex gap-3">
            <input type="text" name="search" value="{ request('search') }" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Buscar...">
            <button class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Buscar</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm"><thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="py-3 pr-4">Título</th><th class="py-3 pr-4">Cliente</th><th class="py-3 pr-4">Tipo</th><th class="py-3 pr-4">Arquivo</th><th class="py-3 pr-4">Ações</th></tr></thead><tbody>@forelse($documents as $document)<tr class="border-b border-slate-100"><td class="py-4 pr-4">{{ $document->title }}</td><td class="py-4 pr-4">{{ $document->client->name ?? '-' }}</td><td class="py-4 pr-4">{{ $document->type }}</td><td class="py-4 pr-4">{{ $document->original_name }}</td><td class="py-4 pr-4 flex gap-2 flex-wrap"><a href="{{ route('documents.show', $document) }}" class="rounded-xl border px-3 py-2">Ver</a><a href="{{ route('documents.download', $document) }}" class="rounded-xl border px-3 py-2">Baixar</a>@can('documents.edit')<a href="{{ route('documents.edit', $document) }}" class="rounded-xl border px-3 py-2">Editar</a>@endcan @can('documents.delete')<form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Excluir documento?')">@csrf @method('DELETE')<button class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-red-700">Excluir</button></form>@endcan</td></tr>@empty<tr><td colspan="5" class="py-6 text-center text-slate-500">Nenhum documento encontrado.</td></tr>@endforelse</tbody></table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
</div>
@endsection
