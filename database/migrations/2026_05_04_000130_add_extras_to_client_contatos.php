<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_contatos', function (Blueprint $table) {
            if (!Schema::hasColumn('client_contatos', 'email_2')) {
                $table->string('email_2', 255)->nullable()->after('email');
            }
            if (!Schema::hasColumn('client_contatos', 'ramal')) {
                $table->string('ramal', 30)->nullable()->after('telefone_2');
            }
            if (!Schema::hasColumn('client_contatos', 'celular')) {
                $table->string('celular', 30)->nullable()->after('ramal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_contatos', function (Blueprint $table) {
            foreach (['email_2', 'ramal', 'celular'] as $col) {
                if (Schema::hasColumn('client_contatos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
