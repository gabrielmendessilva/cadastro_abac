{{-- Aba FINANCEIRO --}}
<div x-data="{ open: false, editId: null }" class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Financeiro</h2>
        <p class="text-sm text-slate-500">E-mails para boletos, contratos ativos e responsáveis.</p>
    </div>

    {{-- E-mails de boletos --}}
    <form method="POST" action="{{ route('clients.update', $client) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
        @csrf @method('PUT')
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                E-mails para Contribuição Associativa
                <span class="ml-1 text-xs text-slate-400">(um por linha)</span>
            </label>
            <textarea name="emails_boletos" rows="4"
                      class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('emails_boletos', $client->emails_boletos) }}</textarea>
        </div>
        @can('clients.edit')
            <div class="mt-3 flex justify-end">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar e-mails</button>
            </div>
        @endcan
    </form>

    {{-- Contratos --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Contratos</h3>
                <p class="text-xs text-slate-500">Contratos ativos vinculados ao cliente.</p>
            </div>
            @can('clients.edit')
                <button @click="open = true; editId = null"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                    + Adicionar contrato
                </button>
            @endcan
        </div>

        @if ($client->contratos->isEmpty())
            <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                Nenhum contrato cadastrado.
            </p>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Descrição</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Responsável</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Vencimento</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Status</th>
                            @can('clients.edit')<th></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($client->contratos as $c)
                            <tr>
                                <td class="px-3 py-2">{{ $c->descricao ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $c->responsavel ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $c->dt_vencimento?->format('d/m/Y') ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    @if ($c->ativo)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Ativo</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Inativo</span>
                                    @endif
                                </td>
                                @can('clients.edit')
                                    <td class="px-3 py-2 text-right">
                                        <form method="POST" action="{{ route('clients.contratos.destroy', [$client, $c]) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button onclick="return confirm('Remover contrato?')"
                                                    class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">
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
                        <form method="POST" action="{{ route('clients.contratos.store', $client) }}">
                            @csrf
                            <div class="border-b border-slate-200 px-6 py-4">
                                <h3 class="text-lg font-semibold text-slate-900">Novo contrato</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Descrição</label>
                                    <input type="text" name="descricao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Responsável</label>
                                    <input type="text" name="responsavel" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Vencimento</label>
                                    <input type="date" name="dt_vencimento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                </div>
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="ativo" value="1" checked class="rounded">
                                    Contrato ativo
                                </label>
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                                    <textarea name="observacoes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                <button type="button" @click="open = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
