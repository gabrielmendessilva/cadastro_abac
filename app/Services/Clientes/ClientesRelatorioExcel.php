<?php

namespace App\Services\Clientes;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Planilha do relatório de cadastro, no layout dos relatórios do CONAC:
 * logo + título + filtros no topo, cabeçalho colorido, total no rodapé.
 */
final class ClientesRelatorioExcel
{
    /** Azul-marinho e laranja da logo da ABAC. */
    public const AZUL = '04355A';

    public const LARANJA = 'DD8F2F';

    private const AZUL_CLARO = 'E6EDF3';

    /** Linhas 1-5 ficam para logo, título e metadados; a tabela começa na 6. */
    private const LINHA_CABECALHO = 6;

    /** @param Collection<int,array<string,string|null>> $linhas */
    public function __construct(
        private readonly ClientesRelatorio $relatorio,
        private readonly Collection $linhas,
    ) {}

    /** Escreve o .xlsx no destino (php://output, de dentro do streamDownload). */
    public function escreveEm(string $destino): void
    {
        $planilha = new Spreadsheet;
        $planilha->getProperties()
            ->setCreator('ABAC')
            ->setTitle(ClientesRelatorio::TITULO)
            ->setDescription('Gerado pelo cadastro online da ABAC.');

        $aba = $planilha->getActiveSheet();
        $aba->setTitle('Cadastro');
        $aba->setShowGridlines(false);

        $colunas = ClientesRelatorio::COLUNAS;
        $ultimaColuna = Coordinate::stringFromColumnIndex(count($colunas));

        $this->escreveTopo($aba, $ultimaColuna);
        $this->escreveTabela($aba, $colunas);
        $this->formataTabela($aba, $colunas, $ultimaColuna);

        (new Xlsx($planilha))->save($destino);

        $planilha->disconnectWorksheets();
    }

    private function escreveTopo(Worksheet $aba, string $ultimaColuna): void
    {
        if (is_file(ClientesRelatorio::logo())) {
            $logo = new Drawing;
            $logo->setName('ABAC');
            $logo->setPath(ClientesRelatorio::logo());
            $logo->setHeight(58);
            $logo->setCoordinates('A1');
            $logo->setOffsetX(6);
            $logo->setOffsetY(6);
            $logo->setWorksheet($aba);
        }

        $aba->getRowDimension(1)->setRowHeight(24);
        $aba->getRowDimension(2)->setRowHeight(24);
        $aba->getRowDimension(3)->setRowHeight(18);

        // Título ao lado da logo (a logo ocupa a coluna A, que é larga).
        $aba->mergeCells("B1:{$ultimaColuna}2");
        $aba->setCellValue('B1', mb_strtoupper(ClientesRelatorio::TITULO));
        $aba->getStyle('B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::AZUL);
        $aba->getStyle('B1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $aba->mergeCells("B3:{$ultimaColuna}3");
        $aba->setCellValue('B3', $this->relatorio->subtitulo());
        $aba->getStyle('B3')->getFont()->setSize(10)->getColor()->setRGB('595959');
        $aba->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $aba->mergeCells("A4:{$ultimaColuna}4");
        $aba->setCellValue('A4', 'Gerado em: '.$this->relatorio->geradoEm.'  •  por '.$this->relatorio->geradoPor);
        $aba->getStyle('A4')->getFont()->setSize(9)->getColor()->setRGB('808080');

        // Faixa laranja separando o topo da tabela, como a linha da logo.
        $aba->getRowDimension(5)->setRowHeight(4);
        $aba->getStyle("A5:{$ultimaColuna}5")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::LARANJA);
    }

    private function escreveTabela(Worksheet $aba, array $colunas): void
    {
        $coluna = 1;
        foreach ($colunas as $definicao) {
            $aba->setCellValue([$coluna++, self::LINHA_CABECALHO], $definicao['titulo']);
        }

        $numeroLinha = self::LINHA_CABECALHO + 1;
        foreach ($this->linhas as $linha) {
            $coluna = 1;

            foreach ($colunas as $chave => $definicao) {
                $valor = $linha[$chave] ?? null;
                $celula = [$coluna++, $numeroLinha];

                // CNPJ, telefone e CEP como texto: o Excel come zero à esquerda
                // e transformaria documento em notação científica.
                if ($valor !== null && $valor !== '' && ! empty($definicao['texto'])) {
                    $aba->setCellValueExplicit($celula, (string) $valor, DataType::TYPE_STRING);
                } else {
                    $aba->setCellValue($celula, $valor);
                }
            }

            $numeroLinha++;
        }
    }

    private function formataTabela(Worksheet $aba, array $colunas, string $ultimaColuna): void
    {
        $cabecalho = self::LINHA_CABECALHO;
        $primeiraLinha = $cabecalho + 1;
        $total = $this->linhas->count();
        $ultimaLinha = $total > 0 ? $cabecalho + $total : $cabecalho;
        $linhaTotal = $ultimaLinha + 1;

        $estiloCabecalho = $aba->getStyle("A{$cabecalho}:{$ultimaColuna}{$cabecalho}");
        $estiloCabecalho->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $estiloCabecalho->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::AZUL);
        $estiloCabecalho->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $aba->getRowDimension($cabecalho)->setRowHeight(22);

        // Zebra leve para acompanhar a linha em planilha larga.
        for ($linha = $primeiraLinha + 1; $linha <= $ultimaLinha; $linha += 2) {
            $aba->getStyle("A{$linha}:{$ultimaColuna}{$linha}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7F9FB');
        }

        $aba->getStyle("A{$cabecalho}:{$ultimaColuna}{$linhaTotal}")->getBorders()
            ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D0D7DE');

        $aba->mergeCells("A{$linhaTotal}:{$ultimaColuna}{$linhaTotal}");
        $aba->setCellValue("A{$linhaTotal}", 'TOTAL DE CADASTROS: '.number_format($total, 0, ',', '.'));
        $estiloTotal = $aba->getStyle("A{$linhaTotal}:{$ultimaColuna}{$linhaTotal}");
        $estiloTotal->getFont()->setBold(true)->getColor()->setRGB(self::AZUL);
        $estiloTotal->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::AZUL_CLARO);

        $indice = 1;
        foreach ($colunas as $definicao) {
            $aba->getColumnDimension(Coordinate::stringFromColumnIndex($indice++))->setWidth($definicao['largura']);
        }

        foreach (['associado_abac', 'status', 'uf'] as $centralizada) {
            $letra = Coordinate::stringFromColumnIndex(array_search($centralizada, array_keys($colunas), true) + 1);
            $aba->getStyle("{$letra}{$primeiraLinha}:{$letra}{$ultimaLinha}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $aba->freezePane('A'.$primeiraLinha);
        if ($total > 0) {
            $aba->setAutoFilter("A{$cabecalho}:{$ultimaColuna}{$ultimaLinha}");
        }

        $aba->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $aba->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($cabecalho, $cabecalho);
        $aba->getHeaderFooter()->setOddFooter('&LABAC — '.ClientesRelatorio::TITULO.'&RPágina &P de &N');
    }
}
