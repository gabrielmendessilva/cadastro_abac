<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_juridico_contatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->enum('area', ['juridico', 'sinac'])->default('juridico');
            $table->string('nome', 255);
            $table->string('funcao', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telefone', 30)->nullable();
            $table->timestamps();

            $table->index(['client_id', 'area']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_juridico_contatos');
    }
};
