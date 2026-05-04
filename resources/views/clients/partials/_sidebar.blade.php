@php
    $tabs = [
        ['key' => 'geral',       'label' => 'Geral',       'icon' => '👤', 'permission' => null],
        ['key' => 'enderecos',   'label' => 'Endereços',   'icon' => '📍', 'permission' => null],
        ['key' => 'contatos',    'label' => 'Contatos',    'icon' => '📇', 'permission' => null],
        ['key' => 'financeiro',  'label' => 'Financeiro',  'icon' => '💰', 'permission' => null],
        ['key' => 'juridico',    'label' => 'Jurídico',    'icon' => '⚖️', 'permission' => null],
        ['key' => 'secretaria',  'label' => 'Secretaria',  'icon' => '🗂️', 'permission' => null],
        ['key' => 'cadastro',    'label' => 'Cadastro',    'icon' => '📋', 'permission' => null],
        ['key' => 'opcionais',   'label' => 'Opcionais',   'icon' => '➕', 'permission' => null],
        [
            'key' => 'ged',
            'label' => 'GED · Documentos',
            'icon' => '📁',
            'permission' => 'documents.view',
            'children' => \App\Models\Document::CATEGORIES,
        ],
        ['key' => 'tags',        'label' => 'Tags',        'icon' => '🏷️', 'permission' => null],
        ['key' => 'uso_interno', 'label' => 'Uso interno', 'icon' => '🔒', 'permission' => null],
    ];
@endphp

<aside class="lg:w-64 lg:shrink-0">
    <nav x-data="{ openGroup: '{{ $activeTab }}' }"
         class="sticky top-4 flex flex-col gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
        @foreach ($tabs as $tab)
            @if ($tab['permission'] && !auth()->user()->can($tab['permission']))
                @continue
            @endif

            @php
                $isActive = $activeTab === $tab['key'];
                $hasChildren = !empty($tab['children']);
            @endphp

            <div>
                @if ($hasChildren)
                    <button type="button"
                            @click="openGroup = (openGroup === '{{ $tab['key'] }}' ? '' : '{{ $tab['key'] }}')"
                            class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition {{ $isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        <span class="flex items-center gap-2">
                            <span>{{ $tab['icon'] }}</span>
                            <span>{{ $tab['label'] }}</span>
                        </span>
                        <svg :class="openGroup === '{{ $tab['key'] }}' ? 'rotate-90' : ''"
                             class="h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div x-show="openGroup === '{{ $tab['key'] }}'" x-cloak class="ml-4 mt-1 flex flex-col gap-0.5 border-l border-slate-200 pl-3">
                        <a href="{{ route('clients.show', ['client' => $client, 'tab' => $tab['key']]) }}"
                           class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $isActive && empty($activeSubtab) ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:bg-slate-50' }}">
                            Todos
                        </a>
                        @foreach ($tab['children'] as $childKey => $childLabel)
                            <a href="{{ route('clients.show', ['client' => $client, 'tab' => $tab['key'], 'subtab' => $childKey]) }}"
                               class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $isActive && $activeSubtab === $childKey ? 'bg-blue-100 text-blue-700' : 'text-slate-500 hover:bg-slate-50' }}">
                                {{ $childLabel }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ route('clients.show', ['client' => $client, 'tab' => $tab['key']]) }}"
                       class="flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-50' }}">
                        <span>{{ $tab['icon'] }}</span>
                        <span>{{ $tab['label'] }}</span>
                    </a>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
