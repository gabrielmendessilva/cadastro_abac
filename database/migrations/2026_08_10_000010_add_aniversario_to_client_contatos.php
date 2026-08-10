<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria `client_contatos.aniversario` — o dia/mês de aniversário do contato.
 *
 * O RM guarda duas coisas diferentes: `FCFOCONTATO.DATANASCIMENTO` (data
 * completa, 1.377 contatos) e `FCFOCONTATO.OBSERVACAO`, que na prática é o
 * aniversário sem o ano no formato dd/mm (2.708 contatos). Onde os dois existem,
 * 1.361 de 1.363 batem exatamente — ou seja, o segundo é o primeiro sem o ano, e
 * é a única fonte para ~1.345 contatos que não têm data de nascimento.
 *
 * Não dá para reaproveitar `dt_nascimento`: a tela abre esse campo num
 * <input type="date"> e o model casta para date, então "16/09" quebraria a
 * edição do contato. Daí a coluna própria, curta e sem cast.
 *
 * Idempotente por `Schema::hasColumn`: o entrypoint do Docker roda
 * `migrate --force` no boot contra o `.env` montado, que é produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('client_contatos', 'aniversario')) {
            return;
        }

        Schema::table('client_contatos', function (Blueprint $table) {
            $table->string('aniversario', 5)->nullable()->after('dt_nascimento');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('client_contatos', 'aniversario')) {
            return;
        }

        Schema::table('client_contatos', function (Blueprint $table) {
            $table->dropColumn('aniversario');
        });
    }
};
