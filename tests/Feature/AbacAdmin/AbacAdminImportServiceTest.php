<?php

namespace Tests\Feature\AbacAdmin;

use App\Services\AbacAdmin\AbacAdminImportOptions;
use App\Services\AbacAdmin\AbacAdminImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AbacAdminImportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Conexão "abac_admin" (origem) vira um sqlite :memory: separado do default.
        config(['database.connections.abac_admin' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        // Destino (conexão default) — espelha o schema real do u910061074_abac_producao
        // (migrations do repo: colunas core em inglês).
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function ($t) {
                $t->id();
                $t->unsignedBigInteger('cod_omie')->nullable();
                $t->string('name');
                $t->string('fantasy_name')->nullable();
                $t->string('document', 20);
                $t->string('email')->nullable();
                $t->string('phone', 20)->nullable();
                $t->string('mobile', 20)->nullable();
                $t->boolean('status')->default(true);
                $t->text('notes')->nullable();
                $t->string('segmento', 100)->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('client_contatos')) {
            Schema::create('client_contatos', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id');
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('nome')->nullable();
                $t->string('funcao')->nullable();
                $t->string('email')->nullable();
                $t->string('email_2')->nullable();
                $t->string('telefone')->nullable();
                $t->string('telefone_2')->nullable();
                $t->string('obs')->nullable();
                $t->timestamps();
            });
        }

        // Origem — espelha o schema real do u910061074_abac_admin (PT-BR legado,
        // com endereco_id/opcional_id e sem email_2 nos contatos).
        Schema::connection('abac_admin')->create('clients', function ($t) {
            $t->id();
            $t->unsignedBigInteger('cod_omie')->nullable();
            $t->string('nome');
            $t->string('nome_fantasia')->nullable();
            $t->string('cpf_cnpj')->nullable();
            $t->string('email_admin')->nullable();
            $t->string('telefone')->nullable();
            $t->string('celular_admin')->nullable();
            $t->string('status')->nullable();
            $t->text('obs')->nullable();
            $t->text('obs_2')->nullable();
            $t->string('email_2')->nullable();
            $t->string('classificacao')->nullable();
            $t->string('segmentos')->nullable();
            $t->integer('endereco_id')->nullable();
            $t->timestamps();
        });

        Schema::connection('abac_admin')->create('client_contatos', function ($t) {
            $t->id();
            $t->integer('client_id');
            $t->integer('user_id')->nullable();
            $t->string('nome')->nullable();
            $t->string('funcao')->nullable();
            $t->string('email')->nullable();
            $t->string('telefone')->nullable();
            $t->string('telefone_2')->nullable();
            $t->string('obs')->nullable();
            $t->timestamps();
        });
    }

    private function service(): AbacAdminImportService
    {
        return new AbacAdminImportService(logger: new NullLogger());
    }

    private function seedSource(): void
    {
        DB::connection('abac_admin')->table('clients')->insert([
            [
                'id' => 10, 'cod_omie' => 8548618307, 'nome' => 'EMPRESA A', 'nome_fantasia' => 'Fantasia A',
                'cpf_cnpj' => '12.345.678/0001-95', 'email_admin' => 'a@x.com',
                'telefone' => '(11) 1111-1111', 'celular_admin' => '(11) 99999-9999',
                'status' => 'Ativo', 'obs' => 'nota base', 'obs_2' => 'segunda nota',
                'email_2' => 'extra@x.com', 'classificacao' => 'Cat A',
                'segmentos' => 'Consórcio', 'endereco_id' => 5,
                'created_at' => '2019-05-05 00:00:00', 'updated_at' => '2019-05-05 00:00:00',
            ],
            [
                'id' => 20, 'cod_omie' => null, 'nome' => 'EMPRESA B', 'nome_fantasia' => null,
                'cpf_cnpj' => '04.124.922/0001-61', 'email_admin' => 'b@x.com',
                'telefone' => null, 'celular_admin' => null,
                'status' => 'I', 'obs' => null, 'obs_2' => null,
                'email_2' => null, 'classificacao' => null,
                'segmentos' => null, 'endereco_id' => null,
                'created_at' => '2021-07-07 00:00:00', 'updated_at' => '2021-07-07 00:00:00',
            ],
            [
                'id' => 30, 'cod_omie' => null, 'nome' => 'SEM DOCUMENTO LTDA', 'nome_fantasia' => null,
                'cpf_cnpj' => '', 'email_admin' => null,
                'telefone' => null, 'celular_admin' => null,
                'status' => null, 'obs' => null, 'obs_2' => null,
                'email_2' => null, 'classificacao' => null,
                'segmentos' => null, 'endereco_id' => null,
                'created_at' => '2022-01-01 00:00:00', 'updated_at' => '2022-01-01 00:00:00',
            ],
        ]);

        DB::connection('abac_admin')->table('client_contatos')->insert([
            [
                'id' => 1, 'client_id' => 10, 'user_id' => 99, 'nome' => 'Contato A1',
                'funcao' => 'Financeiro', 'email' => 'a1@x.com', 'telefone' => '111',
                'telefone_2' => null, 'obs' => 'obs contato',
                'created_at' => '2019-06-06 00:00:00', 'updated_at' => '2019-06-06 00:00:00',
            ],
            [
                'id' => 2, 'client_id' => 20, 'user_id' => null, 'nome' => 'Contato B1',
                'funcao' => null, 'email' => 'b1@x.com', 'telefone' => '222',
                'telefone_2' => null, 'obs' => null,
                'created_at' => '2021-08-08 00:00:00', 'updated_at' => '2021-08-08 00:00:00',
            ],
            [
                'id' => 3, 'client_id' => 999, 'user_id' => null, 'nome' => 'Órfão',
                'funcao' => null, 'email' => 'orfao@x.com', 'telefone' => '333',
                'telefone_2' => null, 'obs' => null,
                'created_at' => '2021-09-09 00:00:00', 'updated_at' => '2021-09-09 00:00:00',
            ],
        ]);
    }

    public function test_migra_com_de_para_de_colunas_remapeando_client_id_e_pulando_orfaos(): void
    {
        $this->seedSource();

        $report = $this->service()->run(new AbacAdminImportOptions);

        $this->assertSame(3, $report->clientsLidos);
        $this->assertSame(3, $report->clientsCriados);
        $this->assertSame(1, $report->clientsSemDocumento);
        $this->assertSame(3, $report->contatosLidos);
        $this->assertSame(2, $report->contatosCriados);
        $this->assertSame(1, $report->contatosOrfaos);
        $this->assertSame(0, $report->erros);
        $this->assertContains('clients.endereco_id', $report->colunasDescartadas);

        // De-para: nome->name, cpf_cnpj->document, email_admin->email, telefone->phone,
        // celular_admin->mobile, segmentos->segmento; status 'Ativo' -> 1.
        $clientA = DB::table('clients')->where('document', '12.345.678/0001-95')->first();
        $this->assertNotNull($clientA);
        $this->assertSame('EMPRESA A', $clientA->name);
        $this->assertSame('Fantasia A', $clientA->fantasy_name);
        $this->assertSame('a@x.com', $clientA->email);
        $this->assertSame('(11) 1111-1111', $clientA->phone);
        $this->assertSame('(11) 99999-9999', $clientA->mobile);
        $this->assertSame('Consórcio', $clientA->segmento);
        $this->assertSame(8548618307, (int) $clientA->cod_omie); // copiado direto (mesmo nome nos 2 lados)
        $this->assertSame(1, (int) $clientA->status);
        $this->assertSame('2019-05-05 00:00:00', (string) $clientA->created_at);

        // Campos sem coluna no destino preservados em notes, com rótulo.
        $this->assertStringContainsString('nota base', (string) $clientA->notes);
        $this->assertStringContainsString('Obs 2: segunda nota', (string) $clientA->notes);
        $this->assertStringContainsString('E-mails adicionais: extra@x.com', (string) $clientA->notes);
        $this->assertStringContainsString('Classificação: Cat A', (string) $clientA->notes);

        $clientB = DB::table('clients')->where('document', '04.124.922/0001-61')->first();
        $this->assertSame(0, (int) $clientB->status); // 'I' => inativo

        // Remap: contato A1 aponta para o id novo da EMPRESA A; user_id não é copiado.
        $contatoA = DB::table('client_contatos')->where('email', 'a1@x.com')->first();
        $this->assertNotNull($contatoA);
        $this->assertSame((int) $clientA->id, (int) $contatoA->client_id);
        $this->assertNull($contatoA->user_id);

        // Órfão ficou de fora.
        $this->assertSame(0, DB::table('client_contatos')->where('email', 'orfao@x.com')->count());
        $this->assertSame(3, DB::table('clients')->count());
        $this->assertSame(2, DB::table('client_contatos')->count());
    }

    public function test_cliente_existente_por_documento_e_pulado_e_contatos_sao_mesclados(): void
    {
        $existenteId = DB::table('clients')->insertGetId([
            'name' => 'JA EXISTE', 'document' => '12345678000195', // sem máscara no destino
            'email' => 'ja@x.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_contatos')->insert([
            'client_id' => $existenteId, 'nome' => 'Antigo', 'email' => 'a1@x.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource(); // origem tem EMPRESA A com o mesmo CNPJ (formatado)

        $report = $this->service()->run(new AbacAdminImportOptions);

        $this->assertSame(2, $report->clientsCriados); // EMPRESA B + SEM DOCUMENTO
        $this->assertSame(1, $report->clientsPuladosExistentes);
        $this->assertSame(1, $report->contatosPuladosEmail); // a1@x.com já existia
        $this->assertSame(1, $report->contatosCriados); // b1@x.com

        $this->assertSame('JA EXISTE', DB::table('clients')->where('id', $existenteId)->value('name'));
        $this->assertSame(3, DB::table('clients')->count());
    }

    public function test_idempotencia_rodar_duas_vezes_nao_duplica(): void
    {
        $this->seedSource();

        $this->service()->run(new AbacAdminImportOptions);
        $second = $this->service()->run(new AbacAdminImportOptions);

        $this->assertSame(0, $second->clientsCriados);
        $this->assertSame(3, $second->clientsPuladosExistentes); // A e B por documento, SEM DOCUMENTO por nome
        $this->assertSame(0, $second->contatosCriados);
        $this->assertSame(2, $second->contatosPuladosEmail);

        $this->assertSame(3, DB::table('clients')->count());
        $this->assertSame(2, DB::table('client_contatos')->count());
    }

    public function test_mesmo_email_em_clientes_diferentes_migra_para_ambos(): void
    {
        $this->seedSource();

        // O MESMO e-mail como contato de dois clientes diferentes: o dedup é por
        // client_id, então os dois devem ser criados.
        DB::connection('abac_admin')->table('client_contatos')->insert([
            [
                'id' => 4, 'client_id' => 10, 'user_id' => null, 'nome' => 'Compartilhado A',
                'funcao' => null, 'email' => 'compartilhado@x.com', 'telefone' => null,
                'telefone_2' => null, 'obs' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 5, 'client_id' => 20, 'user_id' => null, 'nome' => 'Compartilhado B',
                'funcao' => null, 'email' => 'compartilhado@x.com', 'telefone' => null,
                'telefone_2' => null, 'obs' => null,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $this->service()->run(new AbacAdminImportOptions);

        $rows = DB::table('client_contatos')->where('email', 'compartilhado@x.com')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(2, $rows->pluck('client_id')->unique()->count()); // um para cada cliente
    }

    public function test_dry_run_nao_grava_e_relata_igual_a_execucao_real(): void
    {
        $this->seedSource();

        $dry = $this->service()->run(new AbacAdminImportOptions(dryRun: true));

        $this->assertSame(0, DB::table('clients')->count());
        $this->assertSame(0, DB::table('client_contatos')->count());

        $real = $this->service()->run(new AbacAdminImportOptions);

        $this->assertSame($real->clientsCriados, $dry->clientsCriados);
        $this->assertSame($real->contatosCriados, $dry->contatosCriados);
        $this->assertSame($real->contatosOrfaos, $dry->contatosOrfaos);
        $this->assertSame(3, DB::table('clients')->count());
    }
}
