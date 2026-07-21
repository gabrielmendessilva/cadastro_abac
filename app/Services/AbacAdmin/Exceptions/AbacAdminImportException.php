<?php

namespace App\Services\AbacAdmin\Exceptions;

use RuntimeException;

class AbacAdminImportException extends RuntimeException
{
    public static function tabelaAusente(string $conexao, string $tabela): self
    {
        return new self(
            "Tabela {$tabela} não encontrada na conexão {$conexao}. " .
            'Confira as chaves ABAC_ADMIN_DB_* no .env (ou DB_* para o destino) e as permissões do usuário.'
        );
    }

    public static function colunaObrigatoriaAusente(string $conexao, string $tabela, string $coluna): self
    {
        return new self(
            "Coluna obrigatória {$coluna} não existe em {$tabela} na conexão {$conexao} — impossível migrar."
        );
    }
}
