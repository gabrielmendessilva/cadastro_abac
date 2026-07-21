<?php

namespace App\Services\AbacAdmin;

use Closure;

/**
 * Opções de uma execução do abac-admin:import.
 */
final readonly class AbacAdminImportOptions
{
    public function __construct(
        public bool $dryRun = false,
        public ?int $limit = null,
        public int $chunkSize = 500,
        public string $sourceClientsTable = 'clients',
        public string $sourceContatosTable = 'client_contatos',
        public int $maxWarningSamples = 200,
        /** Chamado a cada chunk processado (clientes ou contatos) com a quantidade de linhas. */
        public ?Closure $onChunk = null,
    ) {}
}
