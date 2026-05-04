<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->enum('tipo', ['principal', 'secundario', 'outro'])->default('outro')->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_enderecos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
