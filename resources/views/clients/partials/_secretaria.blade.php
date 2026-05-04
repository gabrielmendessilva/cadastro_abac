{{-- Aba SECRETARIA --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Secretaria</h2>
        <p class="text-sm text-slate-500">Organograma, mandato do presidente e contatos da secretaria.</p>
    </div>

    @php
        $diasParaTermino = $client->mandato_termino
            ? now()->diffInDays($client->mandato_termino, false)
            : null;
        $alertaMandato = $client->mandato_alerta
            && $diasParaTermino !== null
            && $diasParaTermino >= 0
            && $diasParaTermino <= 90;
    @endphp

    @if ($alertaMandato)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span class="text-lg">⚠️</span>
            <div>
                <strong>Mandato vencendo em {{ (int) $diasParaTermino }} dia(s).</strong>
                Término previsto para {{ $client->mandato_termino->format('d/m/Y') }}.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('clients.update', $client) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
        @csrf @method('PUT')

        <h3 class="mb-3 text-sm font-semibold text-slate-700">Organograma — Presidente</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-700">Presidente atual</label>
                <input type="text" name="presidente_atual" value="{{ old('presidente_atual', $client->presidente_atual) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="mandato_alerta" value="1" @checked($client->mandato_alerta) class="rounded">
                Avisar quando mandato vencer (90 dias antes)
            </label>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Início do mandato</label>
                <input type="date" name="mandato_inicio" value="{{ old('mandato_inicio', $client->mandato_inicio?->format('Y-m-d')) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Término do mandato</label>
                <input type="date" name="mandato_termino" value="{{ old('mandato_termino', $client->mandato_termino?->format('Y-m-d')) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
        </div>

        <h3 class="mb-3 mt-6 text-sm font-semibold text-slate-700">Contatos diretos</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail do presidente</label>
                <input type="email" name="email_presidente" value="{{ old('email_presidente', $client->email_presidente) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail da secretaria / contato na empresa</label>
                <input type="email" name="email_secretaria" value="{{ old('email_secretaria', $client->email_secretaria) }}"
                       class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
        </div>

        @can('clients.edit')
            <div class="mt-4 flex justify-end">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar</button>
            </div>
        @endcan
    </form>
</div>
