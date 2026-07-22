<?php

namespace Tests\Feature\Associados;

use App\Services\Associados\AssociadosSyncOptions;
use App\Services\Associados\AssociadosSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AssociadosSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Conexão do WordPress dos associados (origem) vira um sqlite :memory:.
        config(['database.connections.pgsql-associado' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        // Mapas de teste (explícitos para não depender dos defaults do config real).
        config([
            'associados.connection' => 'pgsql-associado',
            'associados.cnpj_meta_like' => '%cnpj_associada%',
            'associados.meta_map' => [
                'razao_social' => 'name',
                'telefone_empresa' => 'phone',
            ],
            'associados.endereco_meta_map' => [
                '_associada_cep' => 'cep',
                '_associada_endereco' => 'rua',
                '_associada_numero' => 'numero',
                '_associada_complemento' => 'complemento',
                '_associada_bairro' => 'bairro',
                '_associada_cidade' => 'municipio',
                '_associada_uf' => 'estado',
            ],
            'associados.meta_ignore' => ['nickname'],
            'associados.sync.contact_user_id' => 1,
        ]);

        // Destino (conexão default) — colunas relevantes do schema vivo.
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function ($t) {
                $t->id();
                $t->string('name');
                $t->string('fantasy_name')->nullable();
                $t->string('document', 20);
                $t->string('phone', 20)->nullable();
                $t->boolean('status')->default(true);
                $t->boolean('associado_abac')->default(false);
                $t->text('obs_cadastro')->nullable();
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
                $t->string('telefone')->nullable();
                $t->boolean('unlock_whatsApp')->default(false);
                $t->timestamps();
            });
        }

        // Espelha a nulabilidade do banco real: só tipo e complemento aceitam null.
        if (! Schema::hasTable('client_enderecos')) {
            Schema::create('client_enderecos', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id');
                $t->string('tipo')->nullable();
                $t->string('cep');
                $t->string('rua');
                $t->string('numero');
                $t->string('complemento')->nullable();
                $t->string('bairro');
                $t->string('pais');
                $t->string('estado');
                $t->string('cod_ibge');
                $t->string('municipio');
                $t->timestamps();
            });
        }

        // Origem — estrutura mínima do WordPress.
        Schema::connection('pgsql-associado')->create('wp_users', function ($t) {
            $t->increments('ID');
            $t->string('user_login')->default('');
            $t->string('user_email')->default('');
            $t->string('display_name')->default('');
        });

        Schema::connection('pgsql-associado')->create('wp_usermeta', function ($t) {
            $t->increments('umeta_id');
            $t->unsignedInteger('user_id');
            $t->string('meta_key')->nullable();
            $t->text('meta_value')->nullable();
        });
    }

    private function service(): AssociadosSyncService
    {
        return new AssociadosSyncService(logger: new NullLogger);
    }

    /**
     * CNPJ A (12.345.678/0001-95): usuários 1 (maria) e 2 (joao) — o 2 vinculado
     * pela variante CRUA do mesmo CNPJ; o 1 carrega as metas da empresa (razão
     * social, telefone e endereço _associada_*). CNPJ B (04.124.922/0001-61):
     * usuário 3 sem e-mail e usermeta órfã (user 99 não existe em wp_users).
     */
    private function seedSource(): void
    {
        DB::connection('pgsql-associado')->table('wp_users')->insert([
            ['ID' => 1, 'user_login' => 'maria', 'user_email' => 'maria@x.com', 'display_name' => 'Maria Silva'],
            ['ID' => 2, 'user_login' => 'joao', 'user_email' => 'joao@x.com', 'display_name' => 'João Souza'],
            ['ID' => 3, 'user_login' => 'sememail', 'user_email' => '', 'display_name' => 'Sem Email'],
        ]);

        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            // user 1 — primeira meta é o nickname (vira o nome do contato, regra legada)
            ['umeta_id' => 1, 'user_id' => 1, 'meta_key' => 'nickname', 'meta_value' => 'Maria Nick'],
            ['umeta_id' => 2, 'user_id' => 1, 'meta_key' => 'cnpj_associada', 'meta_value' => '12.345.678/0001-95'],
            ['umeta_id' => 3, 'user_id' => 1, 'meta_key' => 'razao_social', 'meta_value' => 'EMPRESA WP A'],
            ['umeta_id' => 4, 'user_id' => 1, 'meta_key' => 'telefone_empresa', 'meta_value' => '(11) 91111-1111'],
            // user 2 — vinculado pela variante crua do MESMO CNPJ
            ['umeta_id' => 5, 'user_id' => 2, 'meta_key' => 'first_name', 'meta_value' => 'João'],
            ['umeta_id' => 6, 'user_id' => 2, 'meta_key' => 'cnpj_associada', 'meta_value' => '12345678000195'],
            // CNPJ B — usuário sem e-mail + usermeta órfã
            ['umeta_id' => 7, 'user_id' => 3, 'meta_key' => 'cnpj_associada', 'meta_value' => '04.124.922/0001-61'],
            ['umeta_id' => 8, 'user_id' => 99, 'meta_key' => 'cnpj_associada', 'meta_value' => '04.124.922/0001-61'],
            // censo de metas
            ['umeta_id' => 9, 'user_id' => 1, 'meta_key' => 'meta_livre', 'meta_value' => 'algo'],
            ['umeta_id' => 10, 'user_id' => 3, 'meta_key' => 'nickname', 'meta_value' => 'Foo'],
            // endereço da empresa do CNPJ A (chaves reais do WP, sem complemento)
            ['umeta_id' => 11, 'user_id' => 1, 'meta_key' => '_associada_cep', 'meta_value' => '01306-901'],
            ['umeta_id' => 12, 'user_id' => 1, 'meta_key' => '_associada_endereco', 'meta_value' => 'RUA AVANHANDAVA'],
            ['umeta_id' => 13, 'user_id' => 1, 'meta_key' => '_associada_numero', 'meta_value' => '126'],
            ['umeta_id' => 14, 'user_id' => 1, 'meta_key' => '_associada_bairro', 'meta_value' => 'BELA VISTA'],
            ['umeta_id' => 15, 'user_id' => 1, 'meta_key' => '_associada_cidade', 'meta_value' => 'SÃO PAULO'],
            ['umeta_id' => 16, 'user_id' => 1, 'meta_key' => '_associada_uf', 'meta_value' => 'SP'],
        ]);
    }

    public function test_cria_cliente_novo_com_metas_mapeadas_contatos_e_endereco(): void
    {
        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(3, $report->cnpjsLidos); // 2 variantes do A + 1 do B
        $this->assertSame(1, $report->cnpjsAgrupados);
        $this->assertSame(2, $report->clientsCriados);
        $this->assertSame(2, $report->contatosCriados);
        $this->assertSame(1, $report->enderecosCriados);
        $this->assertSame(1, $report->usuariosSemEmail);
        $this->assertSame(1, $report->usuariosOrfaos);
        $this->assertSame(0, $report->erros);

        // Cliente A: document FORMATADO, campos do meta_map, flags e carimbo.
        $clientA = DB::table('clients')->where('document', '12.345.678/0001-95')->first();
        $this->assertNotNull($clientA);
        $this->assertSame('EMPRESA WP A', $clientA->name);
        $this->assertSame('(11) 91111-1111', $clientA->phone);
        $this->assertSame(1, (int) $clientA->associado_abac);
        $this->assertSame(1, (int) $clientA->status);
        $this->assertStringContainsString('WordPress dos associados', (string) $clientA->obs_cadastro);

        // Endereço principal do A vindo das metas _associada_*.
        $enderecos = DB::table('client_enderecos')->where('client_id', $clientA->id)->get();
        $this->assertCount(1, $enderecos);
        $end = $enderecos[0];
        $this->assertSame('principal', $end->tipo);
        $this->assertSame('01306-901', $end->cep);
        $this->assertSame('RUA AVANHANDAVA', $end->rua);
        $this->assertSame('126', $end->numero);
        $this->assertNull($end->complemento); // meta ausente
        $this->assertSame('BELA VISTA', $end->bairro);
        $this->assertSame('SÃO PAULO', $end->municipio);
        $this->assertSame('SP', $end->estado);
        $this->assertSame('', $end->pais); // NOT NULL sem meta vira '' (padrão rm:import)
        $this->assertSame('', $end->cod_ibge);

        // Contatos do A: as duas variantes do CNPJ caíram no MESMO cliente.
        $contatos = DB::table('client_contatos')->where('client_id', $clientA->id)->orderBy('email')->get();
        $this->assertCount(2, $contatos);
        $this->assertSame(['joao@x.com', 'maria@x.com'], $contatos->pluck('email')->all());
        $this->assertSame('João', $contatos[0]->nome); // primeira meta do user 2
        $this->assertSame('Maria Nick', $contatos[1]->nome); // primeira meta do user 1
        $this->assertSame([1, 1], $contatos->pluck('user_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(0, (int) $contatos[0]->unlock_whatsApp);

        // Cliente B: sem meta de nome nem endereço — fallback display_name, 0 endereços.
        $clientB = DB::table('clients')->where('document', '04.124.922/0001-61')->first();
        $this->assertNotNull($clientB);
        $this->assertSame('Sem Email', $clientB->name);
        $this->assertSame(0, DB::table('client_contatos')->where('client_id', $clientB->id)->count());
        $this->assertSame(1, DB::table('client_enderecos')->count());

        // Censo: meta_livre e first_name sem de-para; nickname está no ignore.
        $this->assertArrayHasKey('meta_livre', $report->metasNaoMapeadas);
        $this->assertArrayHasKey('first_name', $report->metasNaoMapeadas);
        $this->assertArrayNotHasKey('nickname', $report->metasNaoMapeadas);
        $this->assertArrayNotHasKey('_associada_cep', $report->metasNaoMapeadas); // mapeada p/ endereço
    }

    public function test_atualiza_cliente_existente_por_digitos_sem_tocar_document(): void
    {
        // Destino guarda o documento CRU — o match é por dígitos mesmo assim.
        DB::table('clients')->insert([
            'name' => 'ANTIGO', 'document' => '12345678000195',
            'phone' => null, 'associado_abac' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(1, $report->clientsAtualizados);
        $this->assertSame(1, $report->clientsCriados); // só o B
        $this->assertSame(1, $report->enderecosCriados); // A não tinha endereço principal

        $client = DB::table('clients')->where('document', '12345678000195')->first();
        $this->assertNotNull($client); // document intocado
        $this->assertSame('EMPRESA WP A', $client->name); // meta mapeada sobrescreve
        $this->assertSame('(11) 91111-1111', $client->phone);
        $this->assertSame(1, (int) $client->associado_abac);
        $this->assertSame(2, DB::table('clients')->count());
    }

    public function test_valor_vazio_no_wp_nao_sobrescreve_campo_existente(): void
    {
        DB::table('clients')->insert([
            'name' => 'NOME MANUAL', 'document' => '04.124.922/0001-61',
            'phone' => '(21) 90000-0000', 'associado_abac' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource(); // CNPJ B não tem razao_social nem telefone_empresa no WP

        $report = $this->service()->run(new AssociadosSyncOptions);

        $client = DB::table('clients')->where('document', '04.124.922/0001-61')->first();
        $this->assertSame('NOME MANUAL', $client->name);
        $this->assertSame('(21) 90000-0000', $client->phone);
        $this->assertSame(1, $report->clientsJaSincronizados); // nada a mudar no B
    }

    public function test_endereco_principal_existente_atualiza_sem_apagar_campos_nao_mapeados(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'EMPRESA A', 'document' => '12.345.678/0001-95', 'associado_abac' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_enderecos')->insert([
            'client_id' => $clientId, 'tipo' => 'principal',
            'cep' => '00000-000', 'rua' => 'RUA ANTIGA', 'numero' => '1',
            'complemento' => 'FUNDOS', 'bairro' => 'CENTRO', 'pais' => 'Brasil',
            'estado' => 'SP', 'cod_ibge' => '3550308', 'municipio' => 'SÃO PAULO',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(1, $report->enderecosAtualizados);
        $this->assertSame(0, $report->enderecosCriados); // B não tem metas de endereço

        $enderecos = DB::table('client_enderecos')->where('client_id', $clientId)->get();
        $this->assertCount(1, $enderecos); // atualizou a linha, não criou segunda "principal"
        $end = $enderecos[0];
        $this->assertSame('01306-901', $end->cep);
        $this->assertSame('RUA AVANHANDAVA', $end->rua);
        $this->assertSame('126', $end->numero);
        $this->assertSame('BELA VISTA', $end->bairro);
        $this->assertSame('FUNDOS', $end->complemento); // meta ausente não anula
        $this->assertSame('Brasil', $end->pais); // campo fora do mapa intocado
        $this->assertSame('3550308', $end->cod_ibge);
        $this->assertSame('SÃO PAULO', $end->municipio); // igual — não entrou no payload
    }

    public function test_contato_existente_atualiza_nome_sem_anular_outros_campos(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'EMPRESA A', 'document' => '12.345.678/0001-95',
            'associado_abac' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // E-mail com caixa diferente e campos preenchidos à mão no CRUD.
        DB::table('client_contatos')->insert([
            'client_id' => $clientId, 'user_id' => 7, 'nome' => 'Antiga',
            'funcao' => 'Diretor', 'email' => 'MARIA@x.com', 'telefone' => '999',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(1, $report->contatosAtualizados); // maria: nome mudou
        $this->assertSame(1, $report->contatosCriados); // joao

        $maria = DB::table('client_contatos')->where('client_id', $clientId)
            ->whereRaw("lower(email) = 'maria@x.com'")->get();
        $this->assertCount(1, $maria); // atualizou a linha existente, não duplicou
        $this->assertSame('Maria Nick', $maria[0]->nome);
        $this->assertSame('Diretor', $maria[0]->funcao); // desvio deliberado do legado: não anula
        $this->assertSame('999', $maria[0]->telefone);
        $this->assertSame(1, (int) $maria[0]->user_id); // user_id gerenciado pelo sync
    }

    public function test_usuarios_com_mesmo_email_no_cnpj_nao_flip_flopam_o_contato(): void
    {
        // Contas WP duplicadas com o mesmo e-mail (inclusive caixa diferente) e
        // nomes distintos: vence o menor user_id e a re-execução converge.
        DB::connection('pgsql-associado')->table('wp_users')->insert([
            ['ID' => 1, 'user_login' => 'um', 'user_email' => 'contato@x.com', 'display_name' => 'Um'],
            ['ID' => 2, 'user_login' => 'dois', 'user_email' => 'CONTATO@x.com', 'display_name' => 'Dois'],
        ]);
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            ['umeta_id' => 1, 'user_id' => 1, 'meta_key' => 'nickname', 'meta_value' => 'Nick Um'],
            ['umeta_id' => 2, 'user_id' => 1, 'meta_key' => 'cnpj_associada', 'meta_value' => '12.345.678/0001-95'],
            ['umeta_id' => 3, 'user_id' => 2, 'meta_key' => 'nickname', 'meta_value' => 'Nick Dois'],
            ['umeta_id' => 4, 'user_id' => 2, 'meta_key' => 'cnpj_associada', 'meta_value' => '12.345.678/0001-95'],
        ]);

        $first = $this->service()->run(new AssociadosSyncOptions);
        $second = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(1, $first->contatosCriados);
        $this->assertSame(0, $first->contatosAtualizados);
        $this->assertSame(0, $second->contatosCriados);
        $this->assertSame(0, $second->contatosAtualizados); // convergiu: no-op
        $this->assertSame(1, $second->contatosSemMudanca);

        $contatos = DB::table('client_contatos')->get();
        $this->assertCount(1, $contatos);
        $this->assertSame('Nick Um', $contatos[0]->nome); // menor user_id venceu
    }

    public function test_cnpj_invalido_e_pulado_com_warning(): void
    {
        DB::connection('pgsql-associado')->table('wp_users')->insert([
            ['ID' => 1, 'user_login' => 'x', 'user_email' => 'x@x.com', 'display_name' => 'X'],
        ]);
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            ['umeta_id' => 1, 'user_id' => 1, 'meta_key' => 'cnpj_associada', 'meta_value' => '123'],
            ['umeta_id' => 2, 'user_id' => 1, 'meta_key' => 'cnpj_associada_2', 'meta_value' => '11111111111111'],
        ]);

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(2, $report->cnpjsLidos);
        $this->assertSame(2, $report->cnpjsInvalidos); // curto demais + sequência repetida
        $this->assertSame(0, $report->clientsCriados);
        $this->assertSame(0, DB::table('clients')->count());
        $this->assertNotEmpty($report->warnings);
    }

    public function test_conflito_de_meta_entre_usuarios_vence_o_menor_user_id(): void
    {
        $this->seedSource();
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            ['umeta_id' => 30, 'user_id' => 2, 'meta_key' => 'razao_social', 'meta_value' => 'OUTRA RAZAO'],
        ]);

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(1, $report->conflitosDeMeta);
        $this->assertSame('EMPRESA WP A', DB::table('clients')
            ->where('document', '12.345.678/0001-95')->value('name')); // user 1 < user 2
    }

    public function test_meta_repetida_do_mesmo_usuario_vence_a_de_menor_umeta_id(): void
    {
        $this->seedSource();
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            ['umeta_id' => 40, 'user_id' => 1, 'meta_key' => 'razao_social', 'meta_value' => 'RAZAO POSTERIOR'],
        ]);

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame('EMPRESA WP A', DB::table('clients')
            ->where('document', '12.345.678/0001-95')->value('name')); // umeta_id 3 < 40
        $this->assertSame(0, $report->conflitosDeMeta); // repetição intra-usuário não é conflito
    }

    public function test_destino_com_documento_duplicado_usa_o_menor_id(): void
    {
        $id1 = DB::table('clients')->insertGetId([
            'name' => 'DUP 1', 'document' => '12.345.678/0001-95', 'associado_abac' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $id2 = DB::table('clients')->insertGetId([
            'name' => 'DUP 2', 'document' => '12345678000195', 'associado_abac' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame('EMPRESA WP A', DB::table('clients')->where('id', $id1)->value('name'));
        $this->assertSame('DUP 2', DB::table('clients')->where('id', $id2)->value('name')); // intocado
        $this->assertSame(2, DB::table('client_contatos')->where('client_id', $id1)->count());
        $this->assertSame(0, DB::table('client_contatos')->where('client_id', $id2)->count());
        $this->assertNotEmpty(array_filter(
            $report->warnings,
            fn (array $w): bool => str_contains($w['message'], 'Documento duplicado'),
        ));
    }

    public function test_idempotencia_segunda_execucao_nao_grava_nada(): void
    {
        $this->seedSource();

        $this->service()->run(new AssociadosSyncOptions);
        $second = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame(0, $second->clientsCriados);
        $this->assertSame(0, $second->clientsAtualizados);
        $this->assertSame(2, $second->clientsJaSincronizados);
        $this->assertSame(0, $second->contatosCriados);
        $this->assertSame(0, $second->contatosAtualizados);
        $this->assertSame(2, $second->contatosSemMudanca);
        $this->assertSame(0, $second->enderecosCriados);
        $this->assertSame(0, $second->enderecosAtualizados);
        $this->assertSame(1, $second->enderecosSemMudanca);

        $this->assertSame(2, DB::table('clients')->count());
        $this->assertSame(2, DB::table('client_contatos')->count());
        $this->assertSame(1, DB::table('client_enderecos')->count());
    }

    public function test_dry_run_nao_grava_nada_incluindo_updates(): void
    {
        DB::table('clients')->insert([
            'name' => 'ANTIGO', 'document' => '12345678000195',
            'phone' => null, 'associado_abac' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->seedSource();

        $dry = $this->service()->run(new AssociadosSyncOptions(dryRun: true));

        // Nada gravado: nem create, nem o UPDATE do cliente pré-existente.
        $this->assertSame(1, DB::table('clients')->count());
        $this->assertSame('ANTIGO', DB::table('clients')->value('name'));
        $this->assertSame(0, (int) DB::table('clients')->value('associado_abac'));
        $this->assertSame(0, DB::table('client_contatos')->count());
        $this->assertSame(0, DB::table('client_enderecos')->count());

        $real = $this->service()->run(new AssociadosSyncOptions);

        $this->assertSame($real->clientsCriados, $dry->clientsCriados);
        $this->assertSame($real->clientsAtualizados, $dry->clientsAtualizados);
        $this->assertSame($real->contatosCriados, $dry->contatosCriados);
        $this->assertSame($real->enderecosCriados, $dry->enderecosCriados);
        $this->assertSame($real->usuariosSemEmail, $dry->usuariosSemEmail);
    }

    public function test_limit_corta_a_lista_de_cnpjs(): void
    {
        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions(limit: 1));

        $this->assertSame(1, $report->clientsCriados);
        $this->assertSame(1, DB::table('clients')->count());
    }

    public function test_limit_conta_grupos_e_nao_separa_variantes_do_mesmo_cnpj(): void
    {
        DB::connection('pgsql-associado')->table('wp_users')->insert([
            ['ID' => 1, 'user_login' => 'a', 'user_email' => 'a@x.com', 'display_name' => 'A'],
            ['ID' => 2, 'user_login' => 'b', 'user_email' => 'b@x.com', 'display_name' => 'B'],
            ['ID' => 3, 'user_login' => 'c', 'user_email' => 'c@x.com', 'display_name' => 'C'],
        ]);
        // Duas variantes do MESMO CNPJ que ordenam ANTES do segundo CNPJ.
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert([
            ['umeta_id' => 1, 'user_id' => 1, 'meta_key' => 'cnpj_associada', 'meta_value' => '04.124.922/0001-61'],
            ['umeta_id' => 2, 'user_id' => 2, 'meta_key' => 'cnpj_associada', 'meta_value' => '04124922000161'],
            ['umeta_id' => 3, 'user_id' => 3, 'meta_key' => 'cnpj_associada', 'meta_value' => '12.345.678/0001-95'],
        ]);

        $report = $this->service()->run(new AssociadosSyncOptions(limit: 1));

        // limit=1 conta GRUPOS: o CNPJ 04... inteiro, com os contatos das DUAS variantes.
        $this->assertSame(1, $report->clientsCriados);
        $client = DB::table('clients')->first();
        $this->assertSame('04.124.922/0001-61', $client->document);
        $this->assertSame(2, DB::table('client_contatos')->where('client_id', $client->id)->count());
    }

    public function test_count_cnpjs_casa_com_os_grupos_processados(): void
    {
        $this->seedSource();

        $service = $this->service();

        $this->assertSame(2, $service->countCnpjs(new AssociadosSyncOptions)); // A (2 variantes) + B
        $this->assertSame(1, $service->countCnpjs(new AssociadosSyncOptions(limit: 1)));
    }

    public function test_discover_lista_metas_com_status_sem_gravar(): void
    {
        $this->seedSource();

        $rows = $this->service()->discover();

        $byKey = collect($rows)->keyBy('meta_key');
        $this->assertSame('mapeada', $byKey['razao_social']['status']);
        $this->assertSame('mapeada (endereço)', $byKey['_associada_cep']['status']);
        $this->assertSame('cnpj (chave)', $byKey['cnpj_associada']['status']);
        $this->assertSame('ignorada', $byKey['nickname']['status']);
        $this->assertSame('NÃO MAPEADA', $byKey['meta_livre']['status']);
        $this->assertSame(1, $byKey['razao_social']['linhas']);
        $this->assertSame(1, $byKey['meta_livre']['linhas']);
        $this->assertSame(4, $byKey['cnpj_associada']['linhas']); // users 1, 2, 3 e 99
        $this->assertSame(2, $byKey['nickname']['usuarios']); // users 1 e 3

        $this->assertSame(0, DB::table('clients')->count());
        $this->assertSame(0, DB::table('client_contatos')->count());
        $this->assertSame(0, DB::table('client_enderecos')->count());
    }

    public function test_meta_map_para_coluna_proibida_inexistente_ou_nao_textual_e_descartado(): void
    {
        config(['associados.meta_map' => [
            'razao_social' => 'name',
            'meta_perigosa' => 'document', // proibida (identidade)
            'meta_flag' => 'status', // proibida (flag gerenciada)
            'meta_bool' => 'associado_abac', // proibida (flag gerenciada)
            'meta_sumida' => 'coluna_que_nao_existe',
        ]]);

        $this->seedSource();

        $report = $this->service()->run(new AssociadosSyncOptions);

        $this->assertCount(4, $report->colunasDescartadas);
        $this->assertSame('EMPRESA WP A', DB::table('clients')
            ->where('document', '12.345.678/0001-95')->value('name')); // o mapa válido segue valendo
        // Nada além do gerenciado pelo sync foi tocado nas flags.
        $this->assertSame(1, (int) DB::table('clients')->where('document', '12.345.678/0001-95')->value('status'));
    }
}
