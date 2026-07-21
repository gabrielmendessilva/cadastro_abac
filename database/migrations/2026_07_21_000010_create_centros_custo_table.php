<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Centro de custo do cliente, importado do TOTVS RM (GCCUSTO via FCFODEF) pelo rm:import.
 * Segue o padrão das demais tabelas satélite do cliente (client_contatos, client_enderecos):
 * vínculo direto por client_id, sem nenhuma coluna de referência ao RM.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guard: migrate --force roda em produção sobre banco legado que pode divergir.
        if (Schema::hasTable('centros_custo')) {
            return;
        }

        Schema::create('centros_custo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('codigo', 30)->nullable();               // código do próprio centro de custo (ex.: '01.001')
            $table->string('nome', 150)->nullable();
            $table->string('codigo_reduzido', 30)->nullable();
            $table->string('classificacao', 60)->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('permite_lancamentos')->default(true);
            $table->string('responsavel', 120)->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'codigo']);                // idempotência da importação
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centros_custo');
    }
};
