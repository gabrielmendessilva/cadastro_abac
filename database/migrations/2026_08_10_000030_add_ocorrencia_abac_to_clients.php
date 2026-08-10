<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria `clients.ocorrencia_abac`, par do já existente `situacao_abac`.
 *
 * A secretaria filtrava a consulta de aniversariantes por
 * `FCFOCOMPL.STATUS = 'OK' AND FCFOCOMPL.OCORRENCIA = 'OK'` — o recorte de
 * "cadastro em ordem", que reduz 6.391 cli/for a 657. `STATUS` vai para
 * `situacao_abac` (coluna que já existia vazia, exibida na aba Geral como
 * "Situação ABAC"); `OCORRENCIA` não tinha onde morar.
 *
 * Os valores são siglas do RM (OK, CA, FL, LE, IN, SA, NC, CR, LO) e não há
 * dicionário na origem — ficam como vieram, para o negócio traduzir depois.
 *
 * Idempotente por `Schema::hasColumn`: o entrypoint do Docker roda
 * `migrate --force` no boot contra o `.env` montado, que é produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'ocorrencia_abac')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('ocorrencia_abac', 20)->nullable()->after('situacao_abac');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clients', 'ocorrencia_abac')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('ocorrencia_abac');
        });
    }
};
