<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComitesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $comites = [
            'Comitê Antifraudes',
            'Comitê Compliance e Auditoria Interna',
            'Comitê Contábil',
            'Comitê Crédito e Cobrança',
            'Comitê Estudos Econômicos',
            'Comitê Gestão de Grupos',
            'Comitê Gestão de Pessoas',
            'Comitê Inovação',
            'Comitê Internacional',
            'Comitê Jurídico',
            'Comitê Marketing Institucional',
            'Comitê Ouvidoria',
            'Comitê Política de Parceiros',
            'Comitê Tecnologia da Informação',
        ];

        foreach ($comites as $nome) {
            DB::table('comites')->updateOrInsert(
                ['nome' => $nome],
                ['ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
