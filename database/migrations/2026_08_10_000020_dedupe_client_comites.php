<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove vínculos de comitê repetidos e impede que voltem.
 *
 * A carga do RM (`rm:import`) deduplica em memória, o que basta para uma
 * execução mas não para duas ao mesmo tempo: cada processo carrega o estado do
 * banco no início e não enxerga o que o outro grava. Foi assim que a base ficou
 * com 1.553 linhas para 1.050 vínculos reais.
 *
 * O índice único torna a regra estrutural — vale para a carga, para a tela e
 * para qualquer script futuro, com ou sem concorrência.
 *
 * Vínculo sem contato (`contato_id` nulo) fica de fora: em MySQL nulos são
 * sempre distintos num índice único, então esses são lançamentos manuais e não
 * têm par a deduplicar.
 *
 * Idempotente: rodar de novo não acha duplicata nem recria o índice.
 */
return new class extends Migration
{
    private const INDICE = 'client_comites_client_contato_nome_unique';

    public function up(): void
    {
        $this->removeDuplicados();

        if ($this->indiceExiste()) {
            return;
        }

        Schema::table('client_comites', function (Blueprint $table) {
            $table->unique(['client_id', 'contato_id', 'comite_nome'], self::INDICE);
        });
    }

    public function down(): void
    {
        if (! $this->indiceExiste()) {
            return;
        }

        Schema::table('client_comites', function (Blueprint $table) {
            $table->dropUnique(self::INDICE);
        });
    }

    /** Mantém a linha mais antiga de cada trio e descarta o resto. */
    private function removeDuplicados(): void
    {
        $sobreviventes = DB::table('client_comites')
            ->selectRaw('MIN(id) as id')
            ->whereNotNull('contato_id')
            ->groupBy('client_id', 'contato_id', 'comite_nome')
            ->pluck('id');

        DB::table('client_comites')
            ->whereNotNull('contato_id')
            ->whereNotIn('id', $sobreviventes)
            ->delete();
    }

    private function indiceExiste(): bool
    {
        foreach (Schema::getIndexes('client_comites') as $indice) {
            if (($indice['name'] ?? null) === self::INDICE) {
                return true;
            }
        }

        return false;
    }
};
