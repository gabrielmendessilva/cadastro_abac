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
        Schema::create('client_opcionais', function (Blueprint $table) {
            $table->id();
            $table->integer('client_id');
            $table->string('site')->nullable();
            $table->date('inicio_atv')->nullable();
            $table->string('num_abac')->nullable();
            $table->date('dt_f_abac')->nullable();
            $table->string('num_sinac')->nullable();
            $table->date('dt_f_sinac')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_opcionais');
    }
};
