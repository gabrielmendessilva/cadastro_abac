{{-- Redes sociais — usado em Cadastro > Informações da empresa. --}}
<div x-data="{ openRede: false }" class="rounded-2xl border border-slate-200 bg-white p-5">
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-slate-700">Redes sociais</h3>
        @can('clients.edit')
            <button @click="openRede = true"
                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                + Adicionar rede
            </button>
        @endcan
    </div>

    @if ($client->redesSociais->isEmpty())
        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhuma rede social cadastrada.</p>
    @else
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            @foreach ($client->redesSociais as $rede)
                <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <div class="min-w-0 flex-1">
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">{{ \App\Models\ClientRedeSocial::TIPOS[$rede->tipo] }}</span>
                        @if ($rede->rotulo)
                            <span class="text-xs text-slate-500">· {{ $rede->rotulo }}</span>
                        @endif
                        <a href="{{ $rede->url }}" target="_blank" rel="noopener"
                           class="ml-2 break-all text-blue-600 hover:underline">{{ $rede->url }}</a>
                    </div>
                    @can('clients.edit')
                        <form method="POST" action="{{ route('clients.redes.destroy', [$client, $rede]) }}">
                            @csrf @method('DELETE')
                            <button onclick="return confirm('Remover?')"
                                    class="rounded-lg border border-red-200 px-2 py-0.5 text-xs text-red-600 hover:bg-red-50">×</button>
                        </form>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif

    @can('clients.edit')
        <div x-show="openRede" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto bg-black/50">
            <div class="flex min-h-full items-center justify-center p-4">
                <div @click.away="openRede = false" class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
                    <form method="POST" action="{{ route('clients.redes.store', $client) }}">
                        @csrf
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Adicionar rede social</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 p-6 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Tipo *</label>
                                <select name="tipo" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                                    @foreach (\App\Models\ClientRedeSocial::TIPOS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Rótulo</label>
                                <input type="text" name="rotulo" placeholder="Ex.: Perfil oficial" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-slate-700">URL *</label>
                                <input type="url" name="url" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                            <button type="button" @click="openRede = false" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Cancelar</button>
                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
