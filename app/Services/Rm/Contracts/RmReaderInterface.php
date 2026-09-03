<?php

namespace App\Services\Rm\Contracts;

use App\Services\Rm\Exceptions\RmImportException;

/**
 * Leitura pura do TOTVS RM (SQL Server). Devolve arrays crus — 100% mockável em teste.
 *
 * Chaves compostas são serializadas como string: "CODCOLIGADA|CODCFO"
 * (e "CODCOLIGADA|CODCFO|IDCONTATO" para complementos de contato).
 */
interface RmReaderInterface
{
    /**
     * Valida a existência das tabelas/colunas essenciais via INFORMATION_SCHEMA.
     * Falha rápido antes de qualquer escrita no destino.
     *
     * @throws RmImportException
     */
    public function preflight(): void;

    /** @param list<string> $documentos CNPJ/CPF que limitam a leitura; vazio = tudo */
    public function countFcfo(?int $coligada = null, array $documentos = []): int;

    /**
     * Itera FCFO em chunks ordenados por (CODCOLIGADA, CODCFO).
     *
     * @param list<string> $documentos CNPJ/CPF que limitam a leitura; vazio = tudo
     * @param callable(array<int,array<string,mixed>>):void $handle recebe as linhas do chunk
     */
    public function eachFcfoChunk(int $chunkSize, ?int $coligada, ?int $limit, array $documentos, callable $handle): void;

    /**
     * @param  array<int,list<string>>  $codesByColigada  [coligada => [CODCFO, ...]]
     * @return array<string,list<array<string,mixed>>>  "coligada|codcfo" => linhas FCFOCONTATO
     */
    public function contatosForKeys(array $codesByColigada): array;

    /**
     * Defaults (FCFODEF) dos cli/for informados. O join é assimétrico: a coligada
     * do cli/for referenciado é CODCOLCFO; CODCOLIGADA é a coligada onde o default vale.
     *
     * @param  array<int,list<string>>  $codesByColigada
     * @return array<string,list<array<string,mixed>>>  "codcolcfo|codcfo" => linhas FCFODEF
     */
    public function defaultsForKeys(array $codesByColigada): array;

    /**
     * Complementares do cli/for (FCFOCOMPL) — de onde sai o recorte de cadastro
     * em ordem (STATUS/OCORRENCIA). Um cli/for tem no máximo uma linha.
     *
     * @param  array<int,list<string>>  $codesByColigada
     * @return array<string,array<string,mixed>>  "coligada|codcfo" => linha
     */
    public function complementaresForKeys(array $codesByColigada): array;

    /** @return list<array<string,mixed>> todas as linhas de GCCUSTO */
    public function allCentrosCusto(): array;

    /**
     * Tipos de cli/for (FTCF) — a taxonomia apontada por FCFO.CODCOLTCF+CODTCF,
     * onde vivem CAT ESPECIAL / CAT ESPECIAL 2.
     *
     * @return list<array<string,mixed>> todas as linhas de FTCF
     */
    public function allTiposCliFor(): array;

    /**
     * Colunas de FCFOCONTATOCOMPL fora do conjunto padrão (chaves + REC*).
     * Tabela ausente => lista vazia (campos complementares são custom por instalação).
     *
     * @return list<string>
     */
    public function contatoComplCustomColumns(): array;

    /**
     * @param  array<int,list<string>>  $codesByColigada
     * @param  list<string>  $columns  colunas custom a buscar
     * @return array<string,array<string,mixed>>  "coligada|codcfo|idcontato" => linha
     */
    public function contatosComplForKeys(array $codesByColigada, array $columns): array;
}
