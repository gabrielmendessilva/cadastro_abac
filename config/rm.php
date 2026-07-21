<?php

return [
    // Nome da conexão (config/database.php) usada para ler o TOTVS RM.
    'connection' => 'rm',

    // Schema das tabelas de dados. Instalações com auditoria ligada mantêm um
    // schema espelho (TOTVSAUDIT) com uma cópia de cada tabela mais as colunas
    // de log — ler de lá quebra a importação com "Invalid column name 'LOGUSER'".
    'schema' => env('RM_DB_SCHEMA', 'dbo'),

    'import' => [
        // Registros FCFO lidos por chunk. Teto prático ~1000 (limite de 2100
        // parâmetros por query do driver sqlsrv nas buscas de contatos/defaults).
        'chunk' => (int) env('RM_IMPORT_CHUNK', 300),

        // Cria a linha de centros_custo (via client_id) também para clientes que
        // já existiam no banco (nunca altera nada na tabela clients em si).
        'backfill' => true,

        // Introspecta FCFOCONTATOCOMPL e anexa campos custom na obs do contato.
        'include_contato_compl' => true,

        // Teto de warnings detalhados guardados no relatório (o resto só conta).
        'max_warning_samples' => 200,
    ],
];
