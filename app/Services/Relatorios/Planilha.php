<?php

namespace App\Services\Relatorios;

/**
 * O que o RelatorioExcel precisa saber de um relatório para desenhar o topo,
 * o cabeçalho e o rodapé — as linhas vão à parte.
 */
interface Planilha
{
    public function titulo(): string;

    /** Linha logo abaixo do título (filtros aplicados ou descrição do conteúdo). */
    public function subtitulo(): string;

    public function geradoEm(): string;

    public function geradoPor(): string;

    public function nomeDaAba(): string;

    /** Rótulo da linha de total, ex. "Total de cadastros". */
    public function rotuloTotal(): string;

    /**
     * Colunas na ordem da planilha.
     *
     * @return array<string,array{titulo:string,largura:int,tipo?:string,centro?:bool}>
     */
    public function colunas(): array;
}
