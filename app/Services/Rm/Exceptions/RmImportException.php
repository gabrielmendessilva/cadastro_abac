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
     * O destino também tem pré-requisito: a carga escreve em colunas criadas por
     * migration, e o banco vivo nem sempre está na mesma versão do repositório.
     *
     * @param list<string> $colunas
     */
    public static function destinoDesatualizado(string $tabela, array $colunas): self
    {
        return new self(
            "Colunas ausentes em {$tabela} no banco do app: " . implode(', ', $colunas) . '. ' .
            'Rode `php artisan migrate` antes de importar.'
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
