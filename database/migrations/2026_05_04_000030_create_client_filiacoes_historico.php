<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_filiacoes_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['abac', 'sinac']);
            $table->string('num_filiacao', 50)->nullable();
            $table->date('dt_filiacao')->nullable();
            $table->date('dt_desfiliacao')->nullable();
            $table->string('motivo_desfiliacao', 500)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_filiacoes_historico');
    }
};
