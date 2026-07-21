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
        public bool $includeContatoCompl = true,
        public int $maxWarningSamples = 200,
        /** Chamado a cada chunk processado com a quantidade de linhas — usado pela progress bar. */
        public ?Closure $onChunk = null,
    ) {}
}
