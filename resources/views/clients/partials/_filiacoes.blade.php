{{-- Histórico de filiações ABAC/SINAC anteriores --}}
<div x-data="{ open: false, tipo: 'abac' }" class="space-y-2">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700">Filiações anteriores</h3>
        @can('clients.edit')
            <button type="button" @click="open = true; tipo = '{{ $tipo }}'"
                    class="inline-flex items-center gap-1 rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                + Adicionar filiação anterior
            </button>
        @endcan
    </div>

    @php $historico = $client->filiacoesHistorico->where('tipo', $tipo); @endphp

    @if ($historico->isEmpty())
        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Sem filiações anteriores registradas.
        </p>
    @else
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Nº filiação</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Data filiação</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Data desfiliação</th>
                        <th class="px-3 py-2 text-left font-semibold text-slate-500">Motivo</th>
                        @can('clients.edit')<th class="px-3 py-2 text-right font-semibold text-slate-500"></th>@endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($historico as $h)
                        <tr>
                            <td class="px-3 py-2">{{ $h->num_filiacao ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $h->dt_filiacao?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $h->dt_desfiliacao?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $h->motivo_desfiliacao ?: '-' }}</td>
                            @can('clients.edit')
                                <td class="px-3 py-2 text-right">
                                    <form method="POST" action="{{ route('clients.filiacoes.destroy', [$client, $h]) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button onclick="return confirm('Remover esta filiação?')"
                                                class="rounded-lg border border-red-200 px-2 py-0.5 text-[10px] font-medium text-red-600 hover:bg-red-50">
                                            Remover
                                        </button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @can('clients.edit')
        <div x-show="open" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click.away="open = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                    <form method="POST" action="{{ route('clients.filiacoes.store', $client) }}">
                        @csrf
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Adicionar filiação anterior</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                            <input type="hidden" name="tipo" :value="tipo">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Número da filiação</label>
                                <input type="text" name="num_filiacao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Data da filiação</label>
                                <input type="date" name="dt_filiacao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Data da desfiliação</label>
                                <input type="date" name="dt_desfiliacao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Motivo da desfiliação</label>
                                <input type="text" name="motivo_desfiliacao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                                <textarea name="observacoes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="open = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700">Cancelar</button>
                            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
