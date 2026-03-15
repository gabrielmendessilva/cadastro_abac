@extends('layouts.app')
@section('title', 'Detalhes do usuário')
@section('page-title', 'Detalhes do usuário')
@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200 space-y-4">
    <div><span class="text-slate-500">Nome:</span> <strong>{{ $user->name }}</strong></div>
    <div><span class="text-slate-500">E-mail:</span> {{ $user->email }}</div>
    <div><span class="text-slate-500">Perfil:</span> {{ $user->getRoleNames()->first() ?? '-' }}</div>
    <div><span class="text-slate-500">Status:</span> {{ $user->status ? 'Ativo' : 'Inativo' }}</div>
    <a href="{{ route('users.index') }}" class="inline-block rounded-2xl border px-5 py-3">Voltar</a>
</div>
@endsection
