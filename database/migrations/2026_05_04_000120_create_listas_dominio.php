<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tabelas de domínio simples (nome + ativo)
        foreach (['regionais', 'segmentos', 'departamentos_lista', 'funcoes', 'status_options'] as $tabela) {
            Schema::create($tabela, function (Blueprint $table) {
                $table->id();
                $table->string('nome', 150)->unique();
                $table->string('descricao', 500)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('estados', function (Blueprint $table) {
            $table->id();
            $table->string('uf', 2)->unique();
            $table->string('nome', 100);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados');
        Schema::dropIfExists('status_options');
        Schema::dropIfExists('funcoes');
        Schema::dropIfExists('departamentos_lista');
        Schema::dropIfExists('segmentos');
        Schema::dropIfExists('regionais');
    }
};
