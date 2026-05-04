<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListasDominioSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Estados brasileiros (27)
        $estados = [
            ['AC', 'Acre'], ['AL', 'Alagoas'], ['AP', 'Amapá'], ['AM', 'Amazonas'],
            ['BA', 'Bahia'], ['CE', 'Ceará'], ['DF', 'Distrito Federal'], ['ES', 'Espírito Santo'],
            ['GO', 'Goiás'], ['MA', 'Maranhão'], ['MT', 'Mato Grosso'], ['MS', 'Mato Grosso do Sul'],
            ['MG', 'Minas Gerais'], ['PA', 'Pará'], ['PB', 'Paraíba'], ['PR', 'Paraná'],
            ['PE', 'Pernambuco'], ['PI', 'Piauí'], ['RJ', 'Rio de Janeiro'], ['RN', 'Rio Grande do Norte'],
            ['RS', 'Rio Grande do Sul'], ['RO', 'Rondônia'], ['RR', 'Roraima'], ['SC', 'Santa Catarina'],
            ['SP', 'São Paulo'], ['SE', 'Sergipe'], ['TO', 'Tocantins'],
        ];

        foreach ($estados as [$uf, $nome]) {
            DB::table('estados')->updateOrInsert(
                ['uf' => $uf],
                ['nome' => $nome, 'ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Regionais (5 macrorregiões brasileiras)
        $regionais = [
            ['Norte', 'Acre, Amapá, Amazonas, Pará, Rondônia, Roraima, Tocantins'],
            ['Nordeste', 'Alagoas, Bahia, Ceará, Maranhão, Paraíba, Pernambuco, Piauí, Rio Grande do Norte, Sergipe'],
            ['Centro-Oeste', 'Distrito Federal, Goiás, Mato Grosso, Mato Grosso do Sul'],
            ['Sudeste', 'Espírito Santo, Minas Gerais, Rio de Janeiro, São Paulo'],
            ['Sul', 'Paraná, Rio Grande do Sul, Santa Catarina'],
        ];
        foreach ($regionais as [$nome, $desc]) {
            DB::table('regionais')->updateOrInsert(
                ['nome' => $nome],
                ['descricao' => $desc, 'ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Departamentos comuns
        $departamentos = [
            ['Diretoria', null],
            ['Administrativo', null],
            ['Financeiro', null],
            ['Comercial', null],
            ['Jurídico', null],
            ['Recursos Humanos', null],
            ['TI', null],
            ['Operações', null],
            ['Atendimento', null],
            ['Marketing', null],
            ['Ouvidoria', null],
            ['Compliance', null],
            ['Secretaria', null],
        ];
        foreach ($departamentos as [$nome, $desc]) {
            DB::table('departamentos_lista')->updateOrInsert(
                ['nome' => $nome],
                ['descricao' => $desc, 'ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Funções comuns
        $funcoes = [
            'Diretor',
            'Gerente',
            'Coordenador',
            'Supervisor',
            'Analista',
            'Assistente',
            'Auxiliar',
            'Estagiário',
            'Presidente',
            'Vice-Presidente',
            'Conselheiro',
            'Sócio',
            'Administrador',
            'Procurador',
            'Representante Legal',
        ];
        foreach ($funcoes as $nome) {
            DB::table('funcoes')->updateOrInsert(
                ['nome' => $nome],
                ['ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Status options
        $statusList = [
            ['Ativo', 'Cliente em operação normal'],
            ['Inativo', 'Cliente sem operação'],
            ['Pendente', 'Cadastro em análise'],
            ['Bloqueado', 'Cliente com restrição'],
            ['Em desfiliação', 'Processo de desfiliação em andamento'],
            ['Filiado', 'Filiação ativa'],
            ['Desfiliado', 'Sem filiação'],
        ];
        foreach ($statusList as [$nome, $desc]) {
            DB::table('status_options')->updateOrInsert(
                ['nome' => $nome],
                ['descricao' => $desc, 'ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Segmentos comuns para administradoras de consórcio (ABAC)
        $segmentos = [
            ['Imóveis', 'Consórcio de imóveis'],
            ['Veículos leves', 'Consórcio de automóveis e motos'],
            ['Veículos pesados', 'Consórcio de caminhões e máquinas'],
            ['Eletroeletrônicos', 'Consórcio de eletroeletrônicos e eletrodomésticos'],
            ['Serviços', 'Consórcio de serviços (viagens, cirurgias, festas)'],
            ['Embarcações', 'Consórcio de barcos e lanchas'],
            ['Aeronaves', 'Consórcio de aeronaves'],
        ];
        foreach ($segmentos as [$nome, $desc]) {
            DB::table('segmentos')->updateOrInsert(
                ['nome' => $nome],
                ['descricao' => $desc, 'ativo' => 1, 'created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
