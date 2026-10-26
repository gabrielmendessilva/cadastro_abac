<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AvisaDownloadPronto;
use App\Models\Client;
use App\Models\Lista\Regional;
use App\Services\Relatorios\CadastroFiltro;
use App\Services\Relatorios\CadastroRelatorio;
use App\Services\Relatorios\RelatorioExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Relatórios > Cadastro: tela de filtros com prévia e download em Excel/PDF.
 *
 * `relatorios.view` abre a tela; `relatorios.export` baixa o arquivo.
 */
class RelatorioCadastroController extends Controller
{
    use AvisaDownloadPronto;

    /**
     * Cookie do AvisaDownloadPronto (vale também para os relatórios v2).
     * Fica fora da criptografia de cookies (bootstrap/app.php).
     */
    public const COOKIE_PRONTO = 'relatorio_pronto';

    /** Siglas das UFs — o filtro existe mesmo com a lista de domínio vazia. */
    private const UFS = [
        'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA',
        'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO',
    ];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('relatorios.view'), 403);

        $relatorio = $this->relatorio($request);

        return view('relatorios.cadastro', [
            'filtro' => $relatorio->filtro,
            'colunas' => $relatorio->colunas(),
            'linhas' => $relatorio->pagina(),
            'opcoes' => $this->opcoes(),
            'podeExportar' => auth()->user()->can('relatorios.export'),
        ]);
    }

    public function excel(Request $request): StreamedResponse
    {
        abort_unless(auth()->user()->can('relatorios.export'), 403);

        $relatorio = $this->relatorio($request);
        $planilha = new RelatorioExcel($relatorio, $relatorio->linhas());

        // streamDownload: o relatório é derivado, não é documento do GED.
        return $this->avisaPronto($request, response()->streamDownload(
            fn () => $planilha->escreveEm('php://output'),
            $relatorio->nomeDoArquivo('xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ));
    }

    public function pdf(Request $request): Response
    {
        abort_unless(auth()->user()->can('relatorios.export'), 403);

        // dompdf é lento com a base inteira; dá folga para o "todos os cadastros".
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $relatorio = $this->relatorio($request);

        $pdf = Pdf::loadView('relatorios.cadastro-pdf', [
            'titulo' => CadastroRelatorio::TITULO,
            'subtitulo' => $relatorio->subtitulo(),
            'geradoEm' => $relatorio->geradoEm,
            'geradoPor' => $relatorio->geradoPor,
            'colunas' => $relatorio->colunas(),
            'linhas' => $relatorio->linhas(),
            'logo' => $this->logoBase64(),
        ])->setPaper('a4', 'landscape');

        // "Página X de Y" via canvas: counter(pages) do CSS não resolve no dompdf.
        $pdf->render();
        $pdf->getDomPDF()->getCanvas()->page_text(
            740, 570, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.5, 0.5, 0.5]
        );

        return $this->avisaPronto($request, $pdf->download($relatorio->nomeDoArquivo('pdf')));
    }

    private function relatorio(Request $request): CadastroRelatorio
    {
        return new CadastroRelatorio(CadastroFiltro::fromRequest($request), (string) auth()->user()->name);
    }

    /** Opções dos selects, tiradas dos valores que existem na base. */
    private function opcoes(): array
    {
        $distintos = fn (string $coluna) => Client::query()
            ->whereNotNull($coluna)
            ->where($coluna, '!=', '')
            ->where($coluna, '!=', '.')
            ->distinct()
            ->orderBy($coluna)
            ->pluck($coluna);

        return [
            'ufs' => self::UFS,
            'categorias' => $distintos('categoria'),
            'regionais' => Regional::query()->orderBy('nome')->get(['id', 'nome']),
        ];
    }

    /** Logo embutida: o dompdf não precisa buscar nada por HTTP. */
    private function logoBase64(): ?string
    {
        $caminho = CadastroRelatorio::logo();

        return is_file($caminho) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($caminho)) : null;
    }
}
