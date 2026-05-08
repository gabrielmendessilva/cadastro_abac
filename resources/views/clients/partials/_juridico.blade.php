{{-- Aba JURÍDICO --}}
<div x-data="{ openContato: false, areaContato: 'juridico' }" class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Jurídico</h2>
        <p class="text-sm text-slate-500">Contatos de interesse jurídico e SINAC. Sócios/administradores agora ficam em <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'cadastro', 'subtab' => 'sociedade']) }}" class="text-blue-600 hover:underline">Cadastro → Sócio / Administrador</a>.</p>
    </div>

    {{-- Contatos JURÍDICO e SINAC --}}
    @foreach (['juridico' => 'Jurídico', 'sinac' => 'SINAC'] as $area => $areaLabel)
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Contatos de interesse — {{ $areaLabel }}</h3>
                </div>
                @can('clients.edit')
                    <button @click="openContato = true; areaContato = '{{ $area }}'"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                        + Adicionar contato
                    </button>
                @endcan
            </div>

            @php $contatosArea = $client->juridicoContatos->where('area', $area); @endphp
            @if ($contatosArea->isEmpty())
                <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhum contato cadastrado.</p>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Nome</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Função</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Departamento</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">E-mail</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Telefone</th>
                                @can('clients.edit')<th></th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($contatosArea as $c)
                                <tr>
                                    <td class="px-3 py-2">{{ $c->nome }}</td>
                                    <td class="px-3 py-2">{{ $c->funcao ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $c->departamento ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $c->email ?: '-' }}</td>
                                    <td class="px-3 py-2">{{ $c->telefone ?: '-' }}</td>
                                    @can('clients.edit')
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('clients.juridico.destroy', [$client, $c]) }}" class="inline">
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
    @endforeach

    {{-- Observações --}}
    <form method="POST" action="{{ route('clients.update', $client) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Observações Jurídico</label>
                <textarea name="obs_juridico" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_juridico', $client->obs_juridico) }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Observações SINAC</label>
                <textarea name="obs_sinac_juridico" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_sinac_juridico', $client->obs_sinac_juridico) }}</textarea>
            </div>
        </div>
        @can('clients.edit')
            <div class="mt-3 flex justify-end">
                <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar observações</button>
            </div>
        @endcan
    </form>

    @can('clients.edit')
        {{-- Modal: novo contato (jurídico/SINAC) --}}
        <div x-show="openContato" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click.away="openContato = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                    <form method="POST" action="{{ route('clients.juridico.store', $client) }}">
                        @csrf
                        <input type="hidden" name="area" :value="areaContato">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Novo contato</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">Nome *</label>
                                <input type="text" name="nome" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Função</label>
                                <input type="text" name="funcao" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Departamento</label>
                                <input type="text" name="departamento" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label>
                                <input type="email" name="email" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label>
                                <input type="text" name="telefone" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="openContato = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
