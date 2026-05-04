<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach (['endereco_id', 'opcional_id'] as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'endereco_id')) {
                $table->integer('endereco_id')->nullable();
            }
            if (!Schema::hasColumn('clients', 'opcional_id')) {
                $table->integer('opcional_id')->nullable();
            }
        });
    }
};
