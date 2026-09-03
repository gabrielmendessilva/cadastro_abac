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
        /**
         * Só mexe em client_contatos: não cria nem atualiza cliente, e não toca
         * em endereço. CNPJ que ainda não existe no destino é pulado, porque sem
         * cliente não há onde pendurar contato.
         */
        public bool $somenteContatos = false,
        /** Chamado a cada chunk de CNPJs processado, com a quantidade de grupos. */
        public ?Closure $onChunk = null,
    ) {}
}
