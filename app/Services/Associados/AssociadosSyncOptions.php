<?php

namespace App\Services\Associados;

use Closure;

/**
 * Opções de uma execução do associados:sync.
 */
final readonly class AssociadosSyncOptions
{
    public function __construct(
        public bool $dryRun = false,
        public ?int $limit = null,
        public int $chunkSize = 200,
        public int $maxWarningSamples = 200,
        /** Chamado a cada chunk de CNPJs processado, com a quantidade de grupos. */
        public ?Closure $onChunk = null,
    ) {}
}
