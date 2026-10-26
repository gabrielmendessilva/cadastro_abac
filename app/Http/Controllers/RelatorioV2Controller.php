<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AvisaDownloadPronto;
use App\Services\Relatorios\ClientesRelatorio;
use App\Services\Relatorios\ContatosRelatorio;
use App\Services\Relatorios\RelatorioExcel;
use App\Services\Relatorios\TabelaRelatorio;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatórios v2: exportação da base inteira em Excel, sem filtros — uma tela
 * para clientes, outra para contatos.
 *
 * Mesmas permissões do relatório de cadastro: `relatorios.view` abre a tela,
 * `relatorios.export` baixa o arquivo.
 */
class RelatorioV2Controller extends Controller
{
    use AvisaDownloadPronto;

    public function clientes()
    {
        return $this->tela('clientes', ClientesRelatorio::TITULO, ClientesRelatorio::total(), 'cliente', 'clientes', 'relatorios.v2.clientes.excel');
    }

    public function clientesExcel(Request $request): StreamedResponse
    {
        return $this->excel($request, new ClientesRelatorio((string) auth()->user()->name));
    }

    public function contatos()
    {
        return $this->tela('contatos', ContatosRelatorio::TITULO, ContatosRelatorio::total(), 'contato', 'contatos', 'relatorios.v2.contatos.excel');
    }

    public function contatosExcel(Request $request): StreamedResponse
    {
        return $this->excel($request, new ContatosRelatorio((string) auth()->user()->name));
    }

    private function tela(string $aba, string $titulo, int $total, string $singular, string $plural, string $rotaExcel)
    {
        abort_unless(auth()->user()->can('relatorios.view'), 403);

        return view('relatorios.v2', [
            'aba' => $aba,
            'titulo' => $titulo,
            'total' => $total,
            'singular' => $singular,
            'plural' => $plural,
            'rotaExcel' => $rotaExcel,
            'podeExportar' => auth()->user()->can('relatorios.export'),
        ]);
    }

    private function excel(Request $request, TabelaRelatorio $relatorio): StreamedResponse
    {
        abort_unless(auth()->user()->can('relatorios.export'), 403);

        // Base inteira com todas as colunas: dá folga de tempo e memória.
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $planilha = new RelatorioExcel($relatorio, $relatorio->linhas());

        // streamDownload: o relatório é derivado, não é documento do GED.
        return $this->avisaPronto($request, response()->streamDownload(
            fn () => $planilha->escreveEm('php://output'),
            $relatorio->nomeDoArquivo('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ));
    }
}
