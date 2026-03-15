@extends('layouts.app')
@section('title', 'Novo documento')
@section('page-title', 'Novo documento')
@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200"><form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">@csrf
<div class="grid md:grid-cols-2 gap-6">
    <div><label class="block text-sm font-medium mb-2">Cliente</label><select name="client_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required>@foreach($clients as $clientOption)<option value="{{ $clientOption->id }}" @selected(old('client_id', $document->client_id ?? '') == $clientOption->id)>{{ $clientOption->name }}</option>@endforeach</select></div>
    <div><label class="block text-sm font-medium mb-2">Título</label><input type="text" name="title" value="{{ old('title', $document->title ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required></div>
    <div><label class="block text-sm font-medium mb-2">Tipo</label><input type="text" name="type" value="{{ old('type', $document->type ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Vencimento</label><input type="date" name="expiration_date" value="{{ old('expiration_date', isset($document) && $document->expiration_date ? $document->expiration_date->format('Y-m-d') : '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Descrição</label><textarea name="description" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('description', $document->description ?? '') }}</textarea></div>
    <div><label class="block text-sm font-medium mb-2">Arquivo</label><input type="file" name="file" class="w-full rounded-2xl border border-slate-300 px-4 py-3" {{ isset($document) ? '' : 'required' }}></div>
    <div class="flex items-center gap-3 mt-8"><input type="checkbox" name="status" value="1" @checked(old('status', $document->status ?? true))><label>Documento ativo</label></div>
</div>
<div class="mt-6 flex gap-3"><button class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Salvar</button><a href="{{ route('documents.index') }}" class="rounded-2xl border px-5 py-3">Cancelar</a></div></form></div>
@endsection