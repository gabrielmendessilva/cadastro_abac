<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Tipos de endereço: principal/pagamento/entrega (mapear secundario->pagamento, outro->entrega)
        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->string('tipo_novo', 20)->nullable()->after('tipo');
        });

        DB::table('client_enderecos')->where('tipo', 'principal')->update(['tipo_novo' => 'principal']);
        DB::table('client_enderecos')->where('tipo', 'secundario')->update(['tipo_novo' => 'pagamento']);
        DB::table('client_enderecos')->where('tipo', 'outro')->update(['tipo_novo' => 'entrega']);

        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->renameColumn('tipo_novo', 'tipo');
        });

        DB::table('client_enderecos')->whereNull('tipo')->update(['tipo' => 'entrega']);

        // 2) Sócios: novos campos
        Schema::table('client_socios', function (Blueprint $table) {
            if (!Schema::hasColumn('client_socios', 'email')) {
                $table->string('email', 255)->nullable()->after('nome');
            }
            if (!Schema::hasColumn('client_socios', 'telefone')) {
                $table->string('telefone', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('client_socios', 'quota_participacao')) {
                $table->decimal('quota_participacao', 7, 4)->nullable()->after('telefone');
            }
            if (!Schema::hasColumn('client_socios', 'mandato_inicio')) {
                $table->date('mandato_inicio')->nullable()->after('quota_participacao');
            }
            if (!Schema::hasColumn('client_socios', 'mandato_termino')) {
                $table->date('mandato_termino')->nullable()->after('mandato_inicio');
            }
        });

        // 3) Lista master de comitês
        if (!Schema::hasTable('comites')) {
            Schema::create('comites', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 150)->unique();
                $table->string('descricao', 500)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->string('tipo_old', 20)->nullable()->after('tipo');
        });

        DB::table('client_enderecos')->where('tipo', 'principal')->update(['tipo_old' => 'principal']);
        DB::table('client_enderecos')->where('tipo', 'pagamento')->update(['tipo_old' => 'secundario']);
        DB::table('client_enderecos')->where('tipo', 'entrega')->update(['tipo_old' => 'outro']);

        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->renameColumn('tipo_old', 'tipo');
        });

        Schema::table('client_socios', function (Blueprint $table) {
            foreach (['email', 'telefone', 'quota_participacao', 'mandato_inicio', 'mandato_termino'] as $c) {
                if (Schema::hasColumn('client_socios', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        Schema::dropIfExists('comites');
    }
};
