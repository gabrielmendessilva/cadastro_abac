<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Padroniza int(11) → bigint(20) unsigned para alinhar com clients.id e users.id
        DB::statement('ALTER TABLE client_contatos MODIFY client_id BIGINT(20) UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE client_contatos MODIFY user_id BIGINT(20) UNSIGNED NULL');

        // Adiciona foreign keys (com cleanup de dados órfãos antes — já validamos que está limpo)
        $existingFks = collect(DB::select("SHOW CREATE TABLE client_contatos"))
            ->first()->{'Create Table'} ?? '';

        if (!str_contains($existingFks, 'client_contatos_client_id_foreign')) {
            DB::statement('ALTER TABLE client_contatos ADD CONSTRAINT client_contatos_client_id_foreign FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE');
        }

        if (!str_contains($existingFks, 'client_contatos_user_id_foreign')) {
            DB::statement('ALTER TABLE client_contatos ADD CONSTRAINT client_contatos_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        $existingFks = collect(DB::select("SHOW CREATE TABLE client_contatos"))
            ->first()->{'Create Table'} ?? '';

        if (str_contains($existingFks, 'client_contatos_client_id_foreign')) {
            DB::statement('ALTER TABLE client_contatos DROP FOREIGN KEY client_contatos_client_id_foreign');
        }
        if (str_contains($existingFks, 'client_contatos_user_id_foreign')) {
            DB::statement('ALTER TABLE client_contatos DROP FOREIGN KEY client_contatos_user_id_foreign');
        }

        DB::statement('ALTER TABLE client_contatos MODIFY client_id INT(11) NOT NULL');
        DB::statement('ALTER TABLE client_contatos MODIFY user_id INT(11) NULL');
    }
};
