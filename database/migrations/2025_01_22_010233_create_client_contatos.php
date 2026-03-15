<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_contatos', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->integer('user_id');
            $table->string('nome')->nullable();
            $table->string('funcao')->nullable();
            $table->string('dt_nascimento')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('telefone_2')->nullable();
            $table->string('obs')->nullable();
            $table->string('departamento')->nullable();
            $table->string('outro_departamento')->nullable();
            $table->string('representante_legal')->nullable();
            $table->string('comite')->nullable();
            $table->boolean('unlock_whatsApp')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contatos');
    }
};
