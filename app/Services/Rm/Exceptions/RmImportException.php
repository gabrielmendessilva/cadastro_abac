<?php

namespace App\Services\Rm\Exceptions;

use RuntimeException;

class RmImportException extends RuntimeException
{
    public static function tabelaAusente(string $tabela): self
    {
        return new self(
            "Tabela {$tabela} não encontrada no banco do RM. " .
            'Confira RM_DB_DATABASE (o schema esperado é o do Corpore) e as permissões do usuário.'
        );
    }

    /**
     * @param list<string> $colunas
     */
    public static function colunasAusentes(string $tabela, array $colunas): self
    {
        return new self(
            "Colunas ausentes em {$tabela} no banco do RM: " . implode(', ', $colunas) . '. ' .
            'O dicionário desta instalação diverge do esperado — ajuste o RmSqlServerReader antes de importar.'
        );
    }
}
