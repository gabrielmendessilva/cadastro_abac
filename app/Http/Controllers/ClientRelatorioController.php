<?php

namespace App\Http\Controllers;

use App\Services\Clientes\ClientesFiltro;
use App\Services\Clientes\ClientesRelatorio;
use App\Services\Clientes\ClientesRelatorioExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatório de cadastro em Excel e PDF, com os mesmos filtros da lista de
 * clientes (a tela manda a própria query string para cá).
 */
class ClientRelatorioController extends Controller
{
    public function excel(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        $relatorio = $this->relatorio($request);
        $planilha = new ClientesRelatorioExcel($relatorio, $relatorio->linhas());

        // streamDownload: o relatório é derivado, não é documento do GED.
        return response()->streamDownload(
            fn () => $planilha->escreveEm('php://output'),
            $relatorio->nomeDoArquivo('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function pdf(Request $request): Response
    {
        abort_unless(auth()->user()->can('clients.view'), 403);

        // dompdf é lento com a base inteira; dá folga para o "todos os cadastros".
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $relatorio = $this->relatorio($request);

        $pdf = Pdf::loadView('clients.relatorio-pdf', [
            'titulo' => ClientesRelatorio::TITULO,
            'subtitulo' => $relatorio->subtitulo(),
            'geradoEm' => $relatorio->geradoEm,
            'geradoPor' => $relatorio->geradoPor,
            'colunas' => $relatorio->colunasDoPdf(),
            'linhas' => $relatorio->linhas(),
            'logo' => $this->logoBase64(),
        ])->setPaper('a4', 'landscape');

        // "Página X de Y" via canvas: counter(pages) do CSS não resolve no dompdf.
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(
            740, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.5, 0.5, 0.5]
        );

        return $pdf->download($relatorio->nomeDoArquivo('pdf'));
    }

    private function relatorio(Request $request): ClientesRelatorio
    {
        return new ClientesRelatorio(ClientesFiltro::fromRequest($request), (string) auth()->user()->name);
    }

    /** Logo embutida: o dompdf não precisa buscar nada por HTTP. */
    private function logoBase64(): ?string
    {
        $caminho = ClientesRelatorio::logo();

        return is_file($caminho) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($caminho)) : null;
    }
}
