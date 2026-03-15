@extends('layouts.app')
@section('title', 'Detalhes do documento')
@section('page-title', 'Detalhes do documento')
@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200 space-y-4">
    <div><span class="text-slate-500">Título:</span> <strong>{{ $document->title }}</strong></div>
    <div><span class="text-slate-500">Cliente:</span> {{ $document->client->name ?? '-' }}</div>
    <div><span class="text-slate-500">Tipo:</span> {{ $document->type }}</div>
    <div><span class="text-slate-500">Arquivo:</span> {{ $document->original_name }}</div>
    <div><span class="text-slate-500">Descrição:</span> {{ $document->description }}</div>
    <div><span class="text-slate-500">Enviado por:</span> {{ $document->uploader->name ?? '-' }}</div>
    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('documents.download', $document) }}" class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Baixar</a>
        <a href="{{ route('documents.preview', $document) }}" target="_blank" class="rounded-2xl border px-5 py-3">Visualizar</a>
        <a href="{{ route('documents.index') }}" class="rounded-2xl border px-5 py-3">Voltar</a>
    </div>
</div>
@endsection
