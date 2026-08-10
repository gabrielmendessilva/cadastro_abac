<?php

namespace App\Services\Aniversariantes;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Planilha dos aniversariantes do mês.
 *
 * As colunas seguem a ordem da consulta que a secretaria rodava no RM, com os
 * campos que o app tem a mais (função, departamento, celular, dia).
 */
final class AniversariantesExcel
{
    /** rótulo => chave da linha devolvida por AniversariantesQuery. */
    private const COLUNAS = [
        'Empresa' => 'empresa',
        'Nome fantasia' => 'nome_fantasia',
        'Rua' => 'rua',
        'Número' => 'numero',
        'Complemento' => 'complemento',
        'Bairro' => 'bairro',
        'Cidade' => 'cidade',
        'UF' => 'uf',
        'CEP' => 'cep',
        'Telefone da empresa' => 'telefone_empresa',
        'E-mail do contato' => 'email',
        'Contato' => 'contato',
        'Função' => 'funcao',
        'Departamento' => 'departamento',
        'Celular' => 'celular',
        'Telefone do contato' => 'telefone_contato',
        'Dia' => 'dia',
        'Mês' => 'mes',
        'Data de nascimento' => 'dt_nascimento',
        'Aniversário' => 'aniversario',
        'Categoria' => 'categoria',
        'Associado ABAC' => 'associado_abac',
    ];

    /**
     * Colunas que o Excel estragaria se adivinhasse o tipo: CEP e telefone perdem
     * o zero à esquerda, e "16/09" viraria uma data de 16 de setembro deste ano.
     */
    private const TEXTO_FORCADO = ['cep', 'telefone_empresa', 'telefone_contato', 'celular', 'aniversario'];

    /**
     * @param Collection<int,array<string,mixed>> $linhas
     */
    public function __construct(
        private readonly Collection $linhas,
        private readonly string $mes,
    ) {}

    /**
     * Escreve o .xlsx no destino (tipicamente php://output, de dentro do
     * streamDownload): a planilha é derivada e não precisa passar por disco.
     */
    public function escreveEm(string $destino): void
    {
        $planilha = new Spreadsheet();
        $planilha->getProperties()
            ->setTitle('Aniversariantes de ' . $this->mes)
            ->setDescription('Gerado pelo cadastro online da ABAC.');

        $aba = $planilha->getActiveSheet();
        $aba->setTitle(mb_substr($this->mes, 0, 31));

        $this->escreveCabecalho($aba);
        $this->escreveLinhas($aba);
        $this->formata($aba);

        (new Xlsx($planilha))->save($destino);

        $planilha->disconnectWorksheets();
    }

    public function nomeDoArquivo(): string
    {
        $slug = str_replace(' ', '-', mb_strtolower($this->mes));

        return 'aniversariantes-' . preg_replace('/[^a-z0-9\-]/', '', $this->semAcento($slug)) . '.xlsx';
    }

    private function escreveCabecalho(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $aba): void
    {
        $coluna = 1;

        foreach (array_keys(self::COLUNAS) as $rotulo) {
            $aba->setCellValue([$coluna++, 1], $rotulo);
        }
    }

    private function escreveLinhas(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $aba): void
    {
        $numeroLinha = 2;

        foreach ($this->linhas as $linha) {
            $coluna = 1;

            foreach (self::COLUNAS as $chave) {
                $celula = [$coluna++, $numeroLinha];
                $valor = $linha[$chave] ?? null;

                if ($chave === 'associado_abac') {
                    $aba->setCellValue($celula, $valor ? 'Sim' : 'Não');

                    continue;
                }

                if ($chave === 'dt_nascimento' && $valor !== null) {
                    $aba->setCellValue($celula, ExcelDate::PHPToExcel($valor));
                    $aba->getStyle($celula)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

                    continue;
                }

                if ($valor !== null && $valor !== '' && in_array($chave, self::TEXTO_FORCADO, true)) {
                    $aba->setCellValueExplicit($celula, (string) $valor, DataType::TYPE_STRING);

                    continue;
                }

                $aba->setCellValue($celula, $valor);
            }

            $numeroLinha++;
        }
    }

    private function formata(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $aba): void
    {
        $ultimaColuna = $aba->getHighestColumn();
        $ultimaLinha = max(2, $aba->getHighestRow());

        $cabecalho = $aba->getStyle('A1:' . $ultimaColuna . '1');
        $cabecalho->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $cabecalho->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F172A');
        $cabecalho->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $aba->getRowDimension(1)->setRowHeight(24);

        // Cabeçalho sempre à vista e filtro pronto: é assim que a planilha vira
        // ferramenta de trabalho em vez de um despejo de linhas.
        $aba->freezePane('A2');
        $aba->setAutoFilter('A1:' . $ultimaColuna . $ultimaLinha);

        foreach (range('A', $ultimaColuna) as $coluna) {
            $aba->getColumnDimension($coluna)->setAutoSize(true);
        }

        $aba->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $aba->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    }

    private function semAcento(string $valor): string
    {
        return iconv('UTF-8', 'ASCII//TRANSLIT', $valor) ?: $valor;
    }
}
