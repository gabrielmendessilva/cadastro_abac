<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_comites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contato_id')->nullable()->constrained('client_contatos')->nullOnDelete();
            $table->string('comite_nome', 255);
            $table->enum('papel', ['coordenador', 'titular', 'suplente'])->default('titular');
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'comite_nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_comites');
    }
};
