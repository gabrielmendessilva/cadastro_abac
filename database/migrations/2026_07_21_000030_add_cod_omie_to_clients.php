<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona `cod_omie` à tabela `clients`.
 *
 * Por que isso é uma migration nova em vez de estar na `create_clients_table`:
 * o banco de produção `sistema_ged` foi criado a partir de um dump legacy e já
 * tinha essa coluna antes de qualquer migration existir. As migrations
 * históricas do repo ignoraram `cod_omie`, deixando qualquer ambiente novo
 * quebrado (a UI e o CRUD Omie leem/escrevem esse campo).
 *
 * Idempotente por `Schema::hasColumn` — não altera o banco vivo, só cria em
 * ambientes onde ela realmente não existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'cod_omie')) {
                // Alinhado ao banco vivo: bigint unsigned nullable.
                // Fica antes de `nome_fantasia` para agrupar identificadores.
                $table->unsignedBigInteger('cod_omie')->nullable()->after('id');
                $table->index('cod_omie');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'cod_omie')) {
                $table->dropIndex(['cod_omie']);
                $table->dropColumn('cod_omie');
            }
        });
    }
};
