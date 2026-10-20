@extends('layouts.app')
@section('title', 'Relatórios')
@section('page-title', 'Relatórios')

@use('App\Services\Relatorios\CadastroFiltro')
@use('App\Services\Relatorios\CadastroRelatorio')

@php
    $campo = 'w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900';
    $rotulo = 'mb-1 block text-xs font-medium text-slate-500';
    $tituloFaixa = 'mb-3 block text-xs font-semibold uppercase tracking-wide text-slate-500';
    $chip = 'flex cursor-pointer items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700';
@endphp

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="mb-6">
        <h3 class="text-xl font-semibold">Relatório de cadastro</h3>
        <p class="text-slate-500 text-sm">Monte o recorte da base com os filtros, confira a prévia e baixe em Excel ou PDF.</p>
    </div>

    {{-- Cada faixa é um grupo de critérios da antiga consulta do Access.
         Filtros diferentes se combinam com E; opções marcadas no mesmo filtro, com OU.
         `aplicado` diz ao CadastroFiltro que o formulário foi enviado — sem ele,
         a tela abre com o padrão (só ativos). --}}
    <form id="form-relatorio" method="GET" action="{{ route('relatorios.cadastro') }}" class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <input type="hidden" name="aplicado" value="1">

        {{-- FAIXA 1 — quem entra --}}
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-4">
            <span class="{{ $tituloFaixa }}">Tipo de cadastro</span>

            <div class="flex flex-wrap items-center gap-2">
                @foreach(CadastroFiltro::TIPOS as $chave => $nome)
                    <label class="{{ $chip }}">
                        <input type="checkbox" name="tipo[]" value="{{ $chave }}" @checked(in_array($chave, $filtro->tipos, true)) class="h-4 w-4 shrink-0 rounded border-slate-300">
                        {{ $nome }}
                    </label>
                @endforeach
            </div>
            <span class="mt-2 block text-xs text-slate-500">Sem nenhum marcado, entram todos os tipos. Marcar mais de um soma os grupos.</span>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                <div class="min-w-0 md:col-span-2">
                    <label for="f-categoria" class="{{ $rotulo }}">Categoria específica</label>
                    <select id="f-categoria" name="categoria" class="{{ $campo }}">
                        <option value="">Todas</option>
                        @foreach($opcoes['categorias'] as $categoria)
                            <option value="{{ $categoria }}" @selected($filtro->categoria === $categoria)>{{ $categoria }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0">
                    <label for="f-associado-abac" class="{{ $rotulo }}">Associado ABAC</label>
                    <select id="f-associado-abac" name="associado_abac" class="{{ $campo }}">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtro->associadoAbac === '1')>Associados</option>
                        <option value="0" @selected($filtro->associadoAbac === '0')>Não associados</option>
                    </select>
                </div>

                <div class="min-w-0">
                    <label for="f-associado-sinac" class="{{ $rotulo }}">Associado SINAC</label>
                    <select id="f-associado-sinac" name="associado_sinac" class="{{ $campo }}">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtro->associadoSinac === '1')>Associados</option>
                        <option value="0" @selected($filtro->associadoSinac === '0')>Não associados</option>
                    </select>
                </div>

                <div class="min-w-0">
                    <label for="f-status" class="{{ $rotulo }}">Status</label>
                    <select id="f-status" name="status" class="{{ $campo }}">
                        <option value="">Todos</option>
                        <option value="1" @selected($filtro->status === '1')>Ativo</option>
                        <option value="0" @selected($filtro->status === '0')>Inativo</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- FAIXA 2 — onde --}}
        <div class="border-b border-slate-200 px-4 py-4">
            <span class="{{ $tituloFaixa }}">Localização</span>

            <span class="{{ $rotulo }}">UF</span>
            <div class="flex flex-wrap gap-2">
                @foreach($opcoes['ufs'] as $uf)
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="uf[]" value="{{ $uf }}" @checked(in_array($uf, $filtro->ufs, true)) class="h-3.5 w-3.5 shrink-0 rounded border-slate-300">
                        {{ $uf }}
                    </label>
                @endforeach
            </div>
            <span class="mt-2 block text-xs text-slate-500">Sem nenhuma marcada, entram todas. A UF e a cidade vêm dos endereços do cadastro.</span>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="min-w-0">
                    <label for="f-cidade" class="{{ $rotulo }}">Cidade</label>
                    <input id="f-cidade" type="text" name="cidade" value="{{ $filtro->cidade }}" class="{{ $campo }}" placeholder="Cidade">
                </div>

                <div class="min-w-0">
                    <label for="f-regional" class="{{ $rotulo }}">Regional</label>
                    <select id="f-regional" name="regional" class="{{ $campo }}">
                        <option value="">Todas</option>
                        @foreach($opcoes['regionais'] as $regional)
                            <option value="{{ $regional->id }}" @selected($filtro->regionalId === $regional->id)>{{ $regional->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- FAIXA 3 — busca e ordem --}}
        <div class="border-b border-slate-200 px-4 py-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="min-w-0 md:col-span-2">
                    <label for="f-busca" class="{{ $rotulo }}">Busca</label>
                    <input id="f-busca" type="text" name="busca" value="{{ $filtro->busca }}" class="{{ $campo }}" placeholder="Nome, CNPJ/CPF ou e-mail">
                </div>
                <div class="min-w-0">
                    <label for="f-ordem" class="{{ $rotulo }}">Ordenar por</label>
                    <select id="f-ordem" name="ordem" class="{{ $campo }}">
                        @foreach(CadastroFiltro::ORDENS as $chave => $nome)
                            <option value="{{ $chave }}" @selected($filtro->ordem === $chave)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- FAIXA 4 — colunas (o "Mostrar" da grade do Access) --}}
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-4" x-data>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Colunas do relatório</span>
                <div class="flex gap-3 text-xs">
                    <button type="button" class="text-indigo-600 underline"
                            @click="$root.querySelectorAll('input[name=\'colunas[]\']').forEach(c => c.checked = c.dataset.padrao === '1')">Padrão</button>
                    <button type="button" class="text-indigo-600 underline"
                            @click="$root.querySelectorAll('input[name=\'colunas[]\']').forEach(c => c.checked = true)">Todas</button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 md:grid-cols-4 xl:grid-cols-6">
                @foreach(CadastroRelatorio::COLUNAS as $chave => $coluna)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="colunas[]" value="{{ $chave }}" data-padrao="{{ ($coluna['padrao'] ?? false) ? '1' : '0' }}"
                               @checked(array_key_exists($chave, $colunas)) class="h-4 w-4 shrink-0 rounded border-slate-300">
                        {{ $coluna['titulo'] }}
                    </label>
                @endforeach
            </div>
        </div>

        {{-- FAIXA 5 — ações. Excel/PDF submetem o mesmo form (formaction): o
             arquivo sai com o que está preenchido agora, mesmo sem Visualizar antes. --}}
        <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-4 py-3">
            <div class="flex flex-wrap items-center gap-2">
                @if($podeExportar)
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Baixar</span>
                    <button type="submit" formaction="{{ route('relatorios.cadastro.excel') }}" data-download
                            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">Excel</button>
                    <button type="submit" formaction="{{ route('relatorios.cadastro.pdf') }}" data-download
                            class="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700">PDF</button>
                @else
                    <span class="text-xs text-slate-500">Seu perfil pode consultar, mas não baixar o relatório.</span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('relatorios.cadastro') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700">Limpar</a>
                <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Visualizar</button>
            </div>
        </div>
    </form>

    {{-- Prévia --}}
    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
        <h4 class="font-semibold">
            {{ number_format($linhas->total(), 0, ',', '.') }} {{ $linhas->total() === 1 ? 'cadastro encontrado' : 'cadastros encontrados' }}
        </h4>
        <span class="text-xs text-slate-500">Prévia com {{ $linhas->perPage() }} por página — o arquivo baixado leva todos.</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-left text-slate-500">
                    @foreach($colunas as $chave => $coluna)
                        <th class="whitespace-nowrap py-3 pr-4 {{ !empty($coluna['centro']) ? 'text-center' : '' }}">{{ $coluna['titulo'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($linhas as $linha)
                    <tr class="border-b border-slate-100">
                        @foreach($colunas as $chave => $coluna)
                            {{-- Texto longo quebra; o resto (CNPJ, telefone, siglas) fica numa linha só. --}}
                            <td class="py-3 pr-4 {{ !empty($coluna['centro']) ? 'text-center' : '' }} {{ $chave === 'razao_social' ? 'font-medium' : '' }} {{ in_array($chave, ['razao_social', 'nome_fantasia', 'endereco'], true) ? 'min-w-48' : 'whitespace-nowrap' }}">
                                {{ CadastroRelatorio::texto($linha[$chave] ?? null) ?: '-' }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($colunas) }}" class="py-6 text-center text-slate-500">Nenhum cadastro encontrado com esses filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $linhas->links() }}</div>
</div>
{{-- Aviso de "gerando" enquanto o relatório é montado. Na prévia a página
     recarrega e o aviso some sozinho; no download a página fica onde está, então
     o servidor devolve o cookie relatorio_pronto com o token que mandamos e o
     aviso fecha quando ele chega (ver RelatorioCadastroController::avisaPronto). --}}
<div id="relatorio-carregando" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40" role="status" aria-live="polite">
    <div class="flex items-center gap-4 rounded-2xl bg-white px-6 py-5 shadow-xl">
        <svg class="h-8 w-8 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
        </svg>
        <div>
            <div id="relatorio-carregando-titulo" class="font-semibold text-slate-900">Gerando relatório…</div>
            <div class="text-sm text-slate-500">Com muitos cadastros, o PDF pode levar alguns minutos.</div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('form-relatorio');
        const aviso = document.getElementById('relatorio-carregando');
        const titulo = document.getElementById('relatorio-carregando-titulo');
        const COOKIE = 'relatorio_pronto';
        let espera = null;

        const lerCookie = () => document.cookie.split('; ').find(c => c.startsWith(COOKIE + '='))?.split('=')[1];
        const apagarCookie = () => { document.cookie = COOKIE + '=; Max-Age=0; path=/'; };

        function mostrar(texto) {
            titulo.textContent = texto;
            aviso.classList.remove('hidden');
            aviso.classList.add('flex');
        }

        function esconder() {
            aviso.classList.add('hidden');
            aviso.classList.remove('flex');
            clearInterval(espera);
            form.querySelector('input[name="download_token"]')?.remove();
        }

        form.addEventListener('submit', function (evento) {
            const botao = evento.submitter;

            if (!botao || !botao.hasAttribute('data-download')) {
                mostrar('Carregando prévia…');
                return;
            }

            // Token novo a cada clique: o aviso só fecha com a resposta deste download.
            const token = String(Date.now());
            apagarCookie();
            form.querySelector('input[name="download_token"]')?.remove();
            const campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = 'download_token';
            campo.value = token;
            form.appendChild(campo);

            mostrar(botao.textContent.trim() === 'PDF' ? 'Gerando PDF…' : 'Gerando Excel…');

            const inicio = Date.now();
            clearInterval(espera);
            espera = setInterval(function () {
                // Teto de 5 min (o limite do servidor): depois disso, solta a tela.
                if (lerCookie() === token || Date.now() - inicio > 5 * 60 * 1000) {
                    apagarCookie();
                    esconder();
                }
            }, 500);
        });

        // Voltar pelo histórico pode restaurar a página com o aviso aberto.
        window.addEventListener('pageshow', esconder);
    })();
</script>
@endsection
