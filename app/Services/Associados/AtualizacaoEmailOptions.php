<?php

namespace App\Services\Associados;

/**
 * Opções de uma execução do associados:atualizar-emails.
 */
final readonly class AtualizacaoEmailOptions
{
    public function __construct(
        public bool $dryRun = false,
    ) {}
}
