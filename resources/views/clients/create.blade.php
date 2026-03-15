@extends('layouts.app')
@section('title', 'Novo cliente')
@section('page-title', 'Novo cliente')
@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200"><form method="POST" action="{{ route('clients.store') }}">@csrf
<div class="grid md:grid-cols-2 gap-6">
    <div><label class="block text-sm font-medium mb-2">Nome / Razão social</label><input type="text" name="name" value="{{ old('name', $client->name ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required></div>
    <div><label class="block text-sm font-medium mb-2">Nome fantasia</label><input type="text" name="fantasy_name" value="{{ old('fantasy_name', $client->fantasy_name ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">CPF/CNPJ</label><input type="text" name="document" value="{{ old('document', $client->document ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3" required></div>
    <div><label class="block text-sm font-medium mb-2">E-mail</label><input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Telefone</label><input type="text" name="phone" value="{{ old('phone', $client->phone ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Celular</label><input type="text" name="mobile" value="{{ old('mobile', $client->mobile ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">CEP</label><input type="text" name="zipcode" value="{{ old('zipcode', $client->zipcode ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Endereço</label><input type="text" name="address" value="{{ old('address', $client->address ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Número</label><input type="text" name="number" value="{{ old('number', $client->number ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Complemento</label><input type="text" name="complement" value="{{ old('complement', $client->complement ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Bairro</label><input type="text" name="district" value="{{ old('district', $client->district ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">Cidade</label><input type="text" name="city" value="{{ old('city', $client->city ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div><label class="block text-sm font-medium mb-2">UF</label><input type="text" name="state" maxlength="2" value="{{ old('state', $client->state ?? '') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3"></div>
    <div class="md:col-span-2"><label class="block text-sm font-medium mb-2">Observações</label><textarea name="notes" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3">{{ old('notes', $client->notes ?? '') }}</textarea></div>
    <div class="flex items-center gap-3">
        <input type="checkbox" name="status" value="1" @checked(old('status', $client->status ?? true))>
        <label>Cliente ativo</label>
    </div>
</div>
<div class="mt-6 flex gap-3"><button class="rounded-2xl bg-slate-900 px-5 py-3 text-white">Salvar</button><a href="{{ route('clients.index') }}" class="rounded-2xl border px-5 py-3">Cancelar</a></div></form></div>
@endsection