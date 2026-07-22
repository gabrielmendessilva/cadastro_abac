<?php

namespace App\Services\Associados\Exceptions;

use RuntimeException;

class AssociadosSyncException extends RuntimeException
{
    public static function conexaoIndisponivel(string $conexao, string $detalhe): self
    {
        return new self(
            "Não foi possível conectar na conexão {$conexao} (WordPress dos associados). " .
            'Confira DB_HOST_ASS/DB_PORT_ASS/DB_DATABASE_ASS/DB_USERNAME_ASS/DB_PASSWORD_ASS no .env ' .
            "e o acesso remoto do usuário no painel do provedor. Detalhe: {$detalhe}"
        );
    }

    public static function tabelaAusente(string $conexao, string $tabela): self
    {
        return new self(
            "Tabela {$tabela} não encontrada na conexão {$conexao}. " .
            'Confira as chaves DB_*_ASS no .env (ou DB_* para o destino) e as permissões do usuário.'
        );
    }

    public static function colunaObrigatoriaAusente(string $conexao, string $tabela, string $coluna): self
    {
        return new self(
            "Coluna obrigatória {$coluna} não existe em {$tabela} na conexão {$conexao} — impossível sincronizar."
        );
    }
}
