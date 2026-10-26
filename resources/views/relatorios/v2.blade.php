@extends('layouts.app')
@section('title', 'Relatórios')
@section('page-title', 'Relatórios')

@php
    $abas = [
        'clientes' => ['rotulo' => 'Clientes', 'rota' => 'relatorios.v2.clientes'],
        'contatos' => ['rotulo' => 'Contatos', 'rota' => 'relatorios.v2.contatos'],
    ];
    $descricoes = [
        'clientes' => 'Exporta a tabela de clientes inteira — todos os cadastros, ativos e inativos, com todos os campos e o endereço principal.',
        'contatos' => 'Exporta todos os contatos, cada um com a empresa a que pertence (razão social, nome fantasia, CNPJ/CPF, categoria e regional).',
    ];
@endphp

@section('content')
<div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-200">
        @foreach($abas as $chave => $item)
            <a href="{{ route($item['rota']) }}"
               class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium {{ $aba === $chave ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ $item['rotulo'] }}
            </a>
        @endforeach
    </div>

    <div class="mb-6">
        <h3 class="text-xl font-semibold">{{ $titulo }}</h3>
        <p class="text-slate-500 text-sm">{{ $descricoes[$aba] }}</p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
        <div>
            <div class="text-2xl font-semibold text-slate-900">{{ number_format($total, 0, ',', '.') }}</div>
            <div class="text-sm text-slate-500">{{ $total === 1 ? $singular : $plural }} na base</div>
        </div>

        {{-- GET por form para o JavaScript conseguir pendurar o download_token. --}}
        <form id="form-relatorio" method="GET" action="{{ route($rotaExcel) }}">
            @if($podeExportar)
                <button type="submit" data-download
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700">Baixar Excel</button>
            @else
                <span class="text-xs text-slate-500">Seu perfil pode consultar, mas não baixar o relatório.</span>
            @endif
        </form>
    </div>
</div>

{{-- Aviso de "gerando" enquanto o Excel é montado: o servidor devolve o cookie
     relatorio_pronto com o token que mandamos e o aviso fecha quando ele chega
     (ver AvisaDownloadPronto). --}}
<div id="relatorio-carregando" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/40" role="status" aria-live="polite">
    <div class="flex items-center gap-4 rounded-2xl bg-white px-6 py-5 shadow-xl">
        <svg class="h-8 w-8 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
        </svg>
        <div>
            <div class="font-semibold text-slate-900">Gerando Excel…</div>
            <div class="text-sm text-slate-500">Com a base inteira, pode levar alguns minutos.</div>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('form-relatorio');
        const aviso = document.getElementById('relatorio-carregando');
        const COOKIE = 'relatorio_pronto';
        let espera = null;

        const lerCookie = () => document.cookie.split('; ').find(c => c.startsWith(COOKIE + '='))?.split('=')[1];
        const apagarCookie = () => { document.cookie = COOKIE + '=; Max-Age=0; path=/'; };

        function esconder() {
            aviso.classList.add('hidden');
            aviso.classList.remove('flex');
            clearInterval(espera);
            form.querySelector('input[name="download_token"]')?.remove();
        }

        form.addEventListener('submit', function () {
            // Token novo a cada clique: o aviso só fecha com a resposta deste download.
            const token = String(Date.now());
            apagarCookie();
            form.querySelector('input[name="download_token"]')?.remove();
            const campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = 'download_token';
            campo.value = token;
            form.appendChild(campo);

            aviso.classList.remove('hidden');
            aviso.classList.add('flex');

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
