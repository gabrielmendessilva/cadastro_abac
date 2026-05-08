{{-- Sociedade — sócios e administradores. Reutilizado em Cadastro > Sócio/Administrador. --}}
<div x-data="{ openSocio: false }" class="space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Sociedade</h3>
                <p class="text-xs text-slate-500">Sócios e administradores da empresa.</p>
            </div>
            @can('clients.edit')
                <button @click="openSocio = true"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                    + Adicionar
                </button>
            @endcan
        </div>

        @if ($client->socios->isEmpty())
            <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhum sócio/administrador cadastrado.</p>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Papel</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Nome</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">E-mail</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Telefone</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Quota %</th>
                            <th class="px-3 py-2 text-left font-semibold text-slate-500">Mandato</th>
                            @can('clients.edit')<th></th>@endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($client->socios as $s)
                            <tr>
                                <td class="px-3 py-2">
                                    <span class="rounded-full {{ $s->papel === 'administrador' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} px-2 py-0.5 text-xs">
                                        {{ \App\Models\ClientSocio::PAPEIS[$s->papel] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">{{ $s->nome }}</td>
                                <td class="px-3 py-2">{{ $s->email ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $s->telefone ?: '-' }}</td>
                                <td class="px-3 py-2">{{ $s->quota_participacao !== null ? rtrim(rtrim(number_format($s->quota_participacao, 4, ',', ''), '0'), ',') . '%' : '-' }}</td>
                                <td class="px-3 py-2 text-xs text-slate-600">
                                    @if ($s->papel === 'administrador' && ($s->mandato_inicio || $s->mandato_termino))
                                        {{ $s->mandato_inicio?->format('d/m/Y') ?: '?' }} → {{ $s->mandato_termino?->format('d/m/Y') ?: '?' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                @can('clients.edit')
                                    <td class="px-3 py-2 text-right">
                                        <form method="POST" action="{{ route('clients.socios.destroy', [$client, $s]) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button onclick="return confirm('Remover?')" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Remover</button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @can('clients.edit')
        <div x-show="openSocio" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click.away="openSocio = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl" x-data="{ papel: 'socio' }">
                    <form method="POST" action="{{ route('clients.socios.store', $client) }}">
                        @csrf
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Novo sócio / administrador</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Papel</label>
                                <select name="papel" x-model="papel" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                    <option value="socio">Sócio</option>
                                    <option value="administrador">Administrador</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Quota de participação (%)</label>
                                <input type="number" step="0.0001" min="0" max="100" name="quota_participacao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Nome *</label>
                                <input type="text" name="nome" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                <input type="email" name="email" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                <input type="text" name="telefone" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <template x-if="papel === 'administrador'">
                                <div class="md:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-2 rounded-xl border border-purple-200 bg-purple-50 p-3">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Início do mandato</label>
                                        <input type="date" name="mandato_inicio" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Término do mandato</label>
                                        <input type="date" name="mandato_termino" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                    </div>
                                </div>
                            </template>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                                <textarea name="observacoes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="openSocio = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
