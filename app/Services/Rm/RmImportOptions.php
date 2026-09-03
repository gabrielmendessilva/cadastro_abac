<?php

namespace App\Services\Rm;

use Closure;

/**
 * Opções de uma execução do rm:import.
 */
final readonly class RmImportOptions
{
    public function __construct(
        public bool $dryRun = false,
        public ?int $limit = null,
        public ?int $coligada = null,
        public int $chunkSize = 300,
        public bool $backfill = true,
        /**
         * Desativa em clients quem está no RM sem FCFOCOMPL.STATUS = 'OK' e
         * OCORRENCIA = 'OK'. Independe de $backfill: não é preencher buraco em
         * cadastro existente, é a regra de status vinda do RM.
         */
        public bool $desativarForaDeOrdem = true,
        public bool $includeContatoCompl = true,
        /**
         * Restringe a leitura da FCFO a estes documentos (CNPJ/CPF, com ou sem
         * máscara). Vazio = base inteira. É o que permite corrigir um cadastro
         * pontual sem passar por cima de todos os outros.
         *
         * @var list<string>
         */
        public array $documentos = [],
        public int $maxWarningSamples = 200,
        /** Chamado a cada chunk processado com a quantidade de linhas — usado pela progress bar. */
        public ?Closure $onChunk = null,
    ) {}
}
