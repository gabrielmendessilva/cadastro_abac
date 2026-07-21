<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cria em `clients` as colunas que a UI, os FormRequests e o ClientObserver já
 * referenciam mas que nunca existiram no banco.
 *
 * Contexto: o CRUD lia atributos inexistentes (`regional`, `contato_name_admin`,
 * `email_2`...). O Eloquent devolve `null` silenciosamente nesse caso, então a
 * tela caía no fallback `-` mesmo com 326 clientes cadastrados.
 *
 * `regional` vira FK: já existe a tabela de domínio `regionais`, então guardar o
 * texto solto duplicaria a lista. O backfill (`clients:backfill-legado`) resolve
 * o nome do legado para o id.
 *
 * Das demais colunas, só `contato_name_admin` tem dado no legado (138/326). As
 * outras estão 0/326 no próprio `abac_admin` — são criadas apenas para que a UI
 * e a validação parem de apontar para o vazio.
 *
 * Idempotente por `Schema::hasColumn`: o entrypoint do Docker roda
 * `migrate --force` no boot contra o `.env` montado, que é produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                // Único campo novo com relação: aponta para a lista de domínio.
                'regional_id' => fn () => $table->foreignId('regional_id')
                    ->nullable()
                    ->constrained('regionais')
                    ->nullOnDelete(),

                'contato_name_admin' => fn () => $table->string('contato_name_admin', 255)->nullable(),

                // Vazias no legado, mas exibidas na aba "geral" do show.blade.
                'classificacao' => fn () => $table->string('classificacao', 100)->nullable(),
                'categoria' => fn () => $table->string('categoria', 100)->nullable(),
                'inscri_estadual' => fn () => $table->string('inscri_estadual', 50)->nullable(),
                'inscri_municipal' => fn () => $table->string('inscri_municipal', 50)->nullable(),
                'tipo_cliente' => fn () => $table->string('tipo_cliente', 50)->nullable(),
                'situacao_abac' => fn () => $table->string('situacao_abac', 100)->nullable(),
                // Grafia sem o "s" é a do legado — mantida para casar na importação.
                'classificao_administradora' => fn () => $table->string('classificao_administradora', 100)->nullable(),
                'email_conac' => fn () => $table->string('email_conac', 255)->nullable(),
                // Já estava em Client::casts() como date, sem coluna correspondente.
                'dt_bacen' => fn () => $table->date('dt_bacen')->nullable(),
                'obs_2' => fn () => $table->text('obs_2')->nullable(),
                'area_atuacao' => fn () => $table->text('area_atuacao')->nullable(),
            ];

            foreach (range(2, 7) as $i) {
                $columns["email_{$i}"] = fn () => $table->string("email_{$i}", 255)->nullable();
            }

            foreach ($columns as $name => $definition) {
                if (! Schema::hasColumn('clients', $name)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'regional_id')) {
                $table->dropConstrainedForeignId('regional_id');
            }

            $columns = array_merge([
                'contato_name_admin', 'classificacao', 'categoria', 'inscri_estadual',
                'inscri_municipal', 'tipo_cliente', 'situacao_abac',
                'classificao_administradora', 'email_conac', 'dt_bacen', 'obs_2',
                'area_atuacao',
            ], array_map(fn ($i) => "email_{$i}", range(2, 7)));

            $existing = array_filter($columns, fn ($c) => Schema::hasColumn('clients', $c));

            if ($existing) {
                $table->dropColumn(array_values($existing));
            }
        });
    }
};
