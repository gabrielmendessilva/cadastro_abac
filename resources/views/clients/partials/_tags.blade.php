{{-- Aba TAGS --}}
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-slate-900">Tags</h2>
        <p class="text-sm text-slate-500">Etiquetas para classificar o cliente (ex.: Ademicon, Farroupilha).</p>
    </div>

    @if ($client->tags->isEmpty())
        <p class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">Nenhuma tag aplicada.</p>
    @else
        <div class="flex flex-wrap gap-2">
            @foreach ($client->tags as $tag)
                <span class="rounded-full bg-{{ $tag->cor }}-100 px-3 py-1 text-sm text-{{ $tag->cor }}-700">
                    🏷️ {{ $tag->nome }}
                </span>
            @endforeach
        </div>
    @endif

    @can('clients.edit')
        <form method="POST" action="{{ route('clients.tags.sync', $client) }}" class="rounded-2xl border border-slate-200 bg-white p-5">
            @csrf
            <h3 class="mb-3 text-sm font-semibold text-slate-700">Aplicar tags existentes</h3>

            @if ($allTags->isEmpty())
                <p class="text-xs text-slate-500">Nenhuma tag cadastrada. Crie a primeira no formulário abaixo.</p>
            @else
                <div class="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($allTags as $tag)
                        @php $checked = $client->tags->contains($tag->id); @endphp
                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked($checked) class="rounded">
                            <span class="rounded-full bg-{{ $tag->cor }}-100 px-2 py-0.5 text-xs text-{{ $tag->cor }}-700">{{ $tag->nome }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 border-t border-slate-200 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-slate-700">Criar nova tag e aplicar</h3>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <input type="text" name="new_tag_nome" placeholder="Nome da nova tag (opcional)"
                           class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                    <select name="new_tag_cor" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm">
                        @foreach (\App\Models\Tag::CORES_DISPONIVEIS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Salvar tags
                    </button>
                </div>
            </div>
        </form>
    @endcan
</div>
