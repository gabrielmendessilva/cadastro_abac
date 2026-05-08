@php
    $subtab = $activeSubtab ?: 'informacoes';
    $comitesMaster = \App\Models\Lista\Comite::where('ativo', true)->orderBy('nome')->get();
@endphp

<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Cadastro</h2>
        <p class="text-sm text-slate-500">Use o menu lateral para navegar entre as seções.</p>
    </div>

    {{-- Submenu compacto (também útil em mobile) --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        @php
            $subAbas = [
                'informacoes' => 'Informações da empresa',
                'departamentos' => 'Departamentos',
                'comites' => 'Comitês',
                'sociedade' => 'Sócio / Administrador',
            ];
        @endphp
        @foreach ($subAbas as $key => $label)
            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'cadastro', 'subtab' => $key]) }}"
               class="rounded-full px-3 py-1.5 text-xs font-medium {{ $subtab === $key ? 'bg-blue-100 text-blue-700' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
            </a>
        @endforeach
        @can('documents.view')
            <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'ged']) }}"
               class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
                Documentos →
            </a>
        @endcan
    </div>

    @if ($subtab === 'informacoes')
        <form method="POST" action="{{ route('clients.update', $client) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
            @csrf @method('PUT')
            <h3 class="mb-3 text-sm font-semibold text-slate-700">Informações da empresa</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Segmento</label>
                    <input type="text" name="segmento" value="{{ old('segmento', $client->segmento) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Área de Atuação</label>
                    <input type="text" name="area_atuacao" value="{{ old('area_atuacao', $client->area_atuacao) }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Observação</label>
                    <textarea name="obs_cadastro" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">{{ old('obs_cadastro', $client->obs_cadastro) }}</textarea>
                </div>
            </div>
            @can('clients.edit')
                <div class="mt-3 flex justify-end">
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Salvar</button>
                </div>
            @endcan
        </form>
    @endif

    @if ($subtab === 'departamentos')
        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Departamentos / contatos da empresa</h3>
                    <p class="text-xs text-slate-500">Cada contato pertence a um departamento. Gerencie pela aba <strong>Contatos</strong>.</p>
                </div>
                <a href="{{ route('clients.show', ['client' => $client, 'tab' => 'contatos']) }}"
                   class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                    Ir para Contatos →
                </a>
            </div>

            @php
                $departamentos = $client->contatos
                    ->groupBy(fn($c) => $c->departamento ?: 'Sem departamento')
                    ->map->count();
            @endphp

            @if ($departamentos->isEmpty())
                <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    Nenhum contato cadastrado ainda.
                </p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($departamentos as $depNome => $qtd)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                            {{ $depNome }} <span class="text-slate-400">· {{ $qtd }}</span>
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($subtab === 'comites')
        <div x-data="{ openComite: false }" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Comitês</h3>
                    <p class="text-xs text-slate-500">Comitês em que o cliente participa. Gerencie a lista mestre em <a href="{{ route('listas.index', ['aba' => 'comites']) }}" class="text-blue-600 hover:underline">Listas → Comitês</a>.</p>
                </div>
                @can('clients.edit')
                    <button @click="openComite = true"
                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                        + Adicionar comitê
                    </button>
                @endcan
            </div>

            @if ($client->comites->isEmpty())
                <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhum comitê cadastrado.</p>
            @else
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Comitê</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Papel</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Contato</th>
                                @can('clients.edit')<th></th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($client->comites as $cm)
                                <tr>
                                    <td class="px-3 py-2">{{ $cm->comite_nome }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                                            {{ \App\Models\ClientComite::PAPEIS[$cm->papel] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">{{ $cm->contato?->nome ?: '-' }}</td>
                                    @can('clients.edit')
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('clients.comites.destroy', [$client, $cm]) }}" class="inline">
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

            @can('clients.edit')
                <div x-show="openComite" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div @click.away="openComite = false" class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                            <form method="POST" action="{{ route('clients.comites.store', $client) }}">
                                @csrf
                                <div class="border-b border-slate-200 px-6 py-4">
                                    <h3 class="text-lg font-semibold text-slate-900">Novo comitê</h3>
                                </div>
                                <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Comitê *</label>
                                        @if ($comitesMaster->isEmpty())
                                            <input type="text" name="comite_nome" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm" placeholder="Digite o nome do comitê">
                                        @else
                                            <select name="comite_nome" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                                <option value="">— selecione —</option>
                                                @foreach ($comitesMaster as $cm)
                                                    <option value="{{ $cm->nome }}">{{ $cm->nome }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Papel</label>
                                        <select name="papel" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            <option value="coordenador">Coordenador</option>
                                            <option value="titular" selected>Titular</option>
                                            <option value="suplente">Suplente</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Contato vinculado</label>
                                        <select name="contato_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                            <option value="">— nenhum —</option>
                                            @foreach ($client->contatos as $ct)
                                                <option value="{{ $ct->id }}">{{ $ct->nome }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
                                        <textarea name="observacoes" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm"></textarea>
                                    </div>
                                </div>
                                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                                    <button type="button" @click="openComite = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    @endif

    @if ($subtab === 'sociedade')
        @include('clients.partials._sociedade')
    @endif
</div>
