<?php

namespace Tests\Feature\Rm;

use App\Models\Client;
use App\Services\Rm\RmImportOptions;
use App\Services\Rm\RmImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\NullLogger;
use Tests\Feature\Rm\Support\FakeRmReader;
use Tests\TestCase;

class RmImportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tabelas mínimas em sqlite (as migrations do repo divergem do banco vivo;
        // aqui replicamos as colunas que o importador escreve/lê, com a mesma
        // nulabilidade/largura do u910061074_abac_producao: núcleo de clients em
        // inglês + extensões legadas em PT-BR.
        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function ($t) {
                $t->id();
                $t->unsignedBigInteger('cod_omie')->nullable();
                $t->string('name');
                $t->string('fantasy_name')->nullable();
                $t->string('document', 20)->unique();
                $t->string('email')->nullable();
                $t->string('phone', 20)->nullable();
                $t->string('mobile', 20)->nullable();
                $t->boolean('status')->default(true);
                $t->text('notes')->nullable();
                $t->string('cpf', 20)->nullable();
                $t->string('rg', 30)->nullable();
                $t->date('dt_nascimento')->nullable();
                $t->date('dt_abertura_empresa')->nullable();
                $t->text('emails_boletos')->nullable();
                $t->text('obs_cadastro')->nullable();
                // extensões legadas (migration 2026_07_21_000040)
                $t->unsignedBigInteger('regional_id')->nullable();
                $t->string('contato_name_admin')->nullable();
                $t->string('inscri_estadual', 50)->nullable();
                $t->string('inscri_municipal', 50)->nullable();
                $t->string('tipo_cliente', 50)->nullable();
                $t->text('area_atuacao')->nullable();
                $t->string('email_2')->nullable();
                $t->string('email_3')->nullable();
                $t->string('email_4')->nullable();
                $t->string('email_5')->nullable();
                $t->string('email_6')->nullable();
                $t->string('email_7')->nullable();
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
                $t->string('dt_nascimento')->nullable();
                $t->string('email')->nullable();
                $t->string('email_2')->nullable();
                $t->string('telefone')->nullable();
                $t->string('telefone_2')->nullable();
                $t->string('ramal', 30)->nullable();
                $t->string('celular', 30)->nullable();
                $t->string('obs')->nullable();
                $t->string('departamento')->nullable();
                $t->string('outro_departamento')->nullable();
                $t->string('representante_legal')->nullable();
                $t->string('comite')->nullable();
                $t->boolean('unlock_whatsApp')->default(false);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('client_enderecos')) {
            // Só tipo e complemento são nullable no banco real — o resto é NOT NULL.
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

        if (! Schema::hasTable('centros_custo')) {
            Schema::create('centros_custo', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id');
                $t->string('codigo', 30)->nullable();
                $t->string('nome', 150)->nullable();
                $t->string('codigo_reduzido', 30)->nullable();
                $t->string('classificacao', 60)->nullable();
                $t->boolean('ativo')->default(true);
                $t->boolean('permite_lancamentos')->default(true);
                $t->string('responsavel', 120)->nullable();
                $t->timestamps();
                $t->unique(['client_id', 'codigo']);
            });
        }

        if (! Schema::hasTable('client_audit_logs')) {
            Schema::create('client_audit_logs', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id')->nullable();
                $t->unsignedBigInteger('user_id')->nullable();
                $t->string('aba')->nullable();
                $t->string('campo')->nullable();
                $t->text('valor_anterior')->nullable();
                $t->text('valor_novo')->nullable();
                $t->string('acao')->nullable();
                $t->timestamps();
            });
        }
    }

    private function service(FakeRmReader $reader): RmImportService
    {
        return new RmImportService(reader: $reader, logger: new NullLogger());
    }

    private function importOptions(bool $dryRun = false, bool $backfill = true, int $chunk = 2): RmImportOptions
    {
        return new RmImportOptions(
            dryRun: $dryRun,
            chunkSize: $chunk,
            backfill: $backfill,
        );
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function fcfoRow(array $overrides = []): array
    {
        return array_merge([
            'CODCOLIGADA' => 1,
            'CODCFO' => '000123',
            'CGCCFO' => '12.345.678/0001-95',
            'NOME' => 'EMPRESA TESTE LTDA',
            'NOMEFANTASIA' => 'Empresa Teste',
            'PAGREC' => 2,
            'ATIVO' => 1,
            'PESSOAFISOUJUR' => 'J',
            'EMAIL' => 'a@x.com;b@x.com',
            'EMAILFISCAL' => 'fiscal@x.com',
            'EMAILPGTO' => 'cobranca@x.com',
            'EMAILENTREGA' => null,
            'TELEFONE' => '(11) 1111-1111',
            'TELEX' => '(11) 99999-9999',
            'CONTATO' => 'João da Silva',
            'INSCRESTADUAL' => 'ISENTO',
            'INSCRMUNICIPAL' => '12345',
            'CIDENTIDADE' => null,
            'DTNASCIMENTO' => null,
            'DTINICATIVIDADES' => '2010-05-10 00:00:00.000',
            'RAMOATIV' => 'Consórcios',
            'CAMPOLIVRE' => 'Observação livre do RM',
            'RUA' => 'Av. Paulista',
            'NUMERO' => '1000',
            'COMPLEMENTO' => 'cj 101',
            'BAIRRO' => 'Bela Vista',
            'CIDADE' => 'São Paulo',
            'CODETD' => 'SP',
            'CEP' => '01310100',
            'PAIS' => 'Brasil',
            'CODMUNICIPIO' => '50308',
            'RUAPGTO' => 'Rua da Cobrança',
            'NUMEROPGTO' => '20',
            'COMPLEMENTOPGTO' => null,
            'BAIRROPGTO' => 'Centro',
            'CIDADEPGTO' => 'São Paulo',
            'CODETDPGTO' => 'SP',
            'CEPPGTO' => '01001000',
            'PAISPAGTO' => 'Brasil',
            'CODMUNICIPIOPGTO' => '50308',
            'RUAENTREGA' => null,
            'NUMEROENTREGA' => null,
            'COMPLEMENTREGA' => null,
            'BAIRROENTREGA' => null,
            'CIDADEENTREGA' => null,
            'CODETDENTREGA' => null,
            'CEPENTREGA' => null,
            'PAISENTREGA' => null,
            'CODMUNICIPIOENTREGA' => null,
        ], $overrides);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function contatoRow(array $overrides = []): array
    {
        return array_merge([
            'CODCOLIGADA' => 1,
            'CODCFO' => '000123',
            'IDCONTATO' => 1,
            'NOME' => 'Maria Souza',
            'EMAIL' => 'maria@x.com',
            'TELEFONE' => '(11) 2222-2222',
            'RAMAL' => '123',
            'FAX' => '(11) 3333-3333',
            'FUNCAO' => 'Financeiro',
            'ATIVO' => 1,
            'DATANASCIMENTO' => '1990-01-02 00:00:00.000',
            'OBSERVACAO' => 'Contato principal',
        ], $overrides);
    }

    public function test_cria_cliente_completo_com_enderecos_contatos_e_centro_custo(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            contatos: [$this->contatoRow()],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => '000123', 'CODCCUSTO' => '01.001']],
            centrosCusto: [[
                'CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => 'Administração',
                'CODREDUZIDO' => '101', 'CODCLASSIFICA' => 'A', 'ATIVO' => 1, 'PERMITELANC' => 1,
                'RESPONSAVEL' => 'Diretoria',
            ]],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->clientsCriados);
        $this->assertSame(2, $report->enderecosCriados);
        $this->assertSame(1, $report->contatosCriados);
        $this->assertSame(1, $report->centrosCustoCriados);
        $this->assertSame(0, $report->erros);

        $client = Client::query()->firstOrFail();
        $this->assertSame('12.345.678/0001-95', $client->document);
        $this->assertSame('EMPRESA TESTE LTDA', $client->name);
        $this->assertSame('Empresa Teste', $client->fantasy_name);
        $this->assertSame('Cliente', $client->tipo_cliente);
        $this->assertTrue($client->status);
        $this->assertSame('(11) 1111-1111', $client->phone);
        $this->assertSame('(11) 99999-9999', $client->mobile);
        $this->assertSame('João da Silva', $client->contato_name_admin);
        $this->assertSame('ISENTO', $client->inscri_estadual);
        $this->assertSame('12345', $client->inscri_municipal);
        $this->assertSame('a@x.com', $client->email);
        $this->assertSame('b@x.com', $client->email_2);
        $this->assertSame('fiscal@x.com', $client->email_3);
        $this->assertSame('cobranca@x.com', $client->email_4);
        $this->assertSame('cobranca@x.com', $client->emails_boletos);
        $this->assertSame('2010-05-10', $client->dt_abertura_empresa->format('Y-m-d'));
        $this->assertSame('Consórcios', $client->area_atuacao);
        $this->assertSame('Observação livre do RM', $client->notes);
        $this->assertStringContainsString('coligada 1, código 000123', $client->obs_cadastro);
        // O RM não tem regional: nada é inventado para regional_id.
        $this->assertNull($client->regional_id);

        // Centro de custo vinculado direto pelo client_id (tabela satélite, sem referência ao RM).
        $cc = DB::table('centros_custo')->where('client_id', $client->id)->first();
        $this->assertNotNull($cc);
        $this->assertSame('01.001', $cc->codigo);
        $this->assertSame('Administração', $cc->nome);

        $enderecos = DB::table('client_enderecos')->where('client_id', $client->id)->orderBy('id')->get();
        $this->assertSame(['principal', 'pagamento'], $enderecos->pluck('tipo')->all());
        $this->assertSame('01310-100', $enderecos[0]->cep);
        $this->assertSame('3550308', $enderecos[0]->cod_ibge);
        $this->assertSame('São Paulo', $enderecos[0]->municipio);

        $contato = DB::table('client_contatos')->where('client_id', $client->id)->first();
        $this->assertNotNull($contato);
        $this->assertSame('maria@x.com', $contato->email);
        $this->assertSame('(11) 3333-3333', $contato->telefone_2);
        $this->assertStringStartsWith('1990-01-02', (string) $contato->dt_nascimento);

        // withoutEvents: o ClientObserver não pode ter gerado auditoria.
        $this->assertSame(0, DB::table('client_audit_logs')->count());
    }

    public function test_cnpj_existente_e_pulado_mas_contatos_novos_entram(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'JA EXISTE',
            'document' => '12.345.678/0001-95',
            'email' => 'ja@x.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('client_contatos')->insert([
            'client_id' => $clientId, 'nome' => 'Antigo', 'email' => 'antigo@x.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(
            // Origem sem máscara: o dedup precisa casar mesmo com o destino formatado.
            fcfo: [$this->fcfoRow(['CGCCFO' => '12345678000195', 'NOME' => 'NOVO NOME'])],
            contatos: [
                $this->contatoRow(['IDCONTATO' => 1, 'NOME' => 'Antigo 2', 'EMAIL' => 'ANTIGO@x.com']),
                $this->contatoRow(['IDCONTATO' => 2, 'NOME' => 'Admin', 'EMAIL' => 'ja@x.com']),
                $this->contatoRow(['IDCONTATO' => 3, 'NOME' => 'Novo', 'EMAIL' => 'novo@x.com']),
            ],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(0, $report->clientsCriados);
        $this->assertSame(1, $report->clientsPuladosExistentes);
        $this->assertSame(1, $report->contatosCriados);
        $this->assertSame(2, $report->contatosPuladosEmail);

        $this->assertSame(1, DB::table('clients')->count());
        $this->assertSame('JA EXISTE', DB::table('clients')->value('name')); // intocado
        $this->assertSame(2, DB::table('client_contatos')->where('client_id', $clientId)->count());
        $this->assertSame(1, DB::table('client_contatos')->where('email', 'novo@x.com')->count());
    }

    public function test_contato_sem_email_deduplica_por_nome_e_sem_chave_e_pulado(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'CLIENTE', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_contatos')->insert([
            'client_id' => $clientId, 'nome' => 'Fulano Silva', 'email' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            contatos: [
                $this->contatoRow(['IDCONTATO' => 1, 'NOME' => 'FULANO   SILVA', 'EMAIL' => null]),
                $this->contatoRow(['IDCONTATO' => 2, 'NOME' => 'Beltrano Costa', 'EMAIL' => null]),
                $this->contatoRow(['IDCONTATO' => 3, 'NOME' => null, 'EMAIL' => null]),
            ],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->contatosPuladosNome);
        $this->assertSame(1, $report->contatosCriados);
        $this->assertSame(1, $report->contatosPuladosSemChave);
        $this->assertSame(2, DB::table('client_contatos')->where('client_id', $clientId)->count());
    }

    public function test_documento_invalido_e_duplicado_dentro_do_rm(): void
    {
        $reader = new FakeRmReader(
            fcfo: [
                $this->fcfoRow(['CODCFO' => 'A0', 'CGCCFO' => '']),
                $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95']),
                $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '12345678000195', 'NOME' => 'DUPLICADO']),
            ],
            contatos: [
                $this->contatoRow(['CODCFO' => 'A0', 'EMAIL' => 'perdido@x.com']),
                $this->contatoRow(['CODCFO' => 'A2', 'NOME' => 'Contato do Duplicado', 'EMAIL' => 'dup@x.com']),
            ],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(3, $report->fcfoLidos);
        $this->assertSame(1, $report->clientsPuladosInvalidos);
        $this->assertSame(1, $report->clientsCriados);
        $this->assertSame(1, $report->duplicadosNoRm);
        $this->assertSame(1, DB::table('clients')->count());

        // Contato do registro duplicado vai para o cliente sobrevivente;
        // contato do registro inválido não entra.
        $client = Client::query()->firstOrFail();
        $this->assertSame(1, DB::table('client_contatos')->where('client_id', $client->id)->where('email', 'dup@x.com')->count());
        $this->assertSame(0, DB::table('client_contatos')->where('email', 'perdido@x.com')->count());
    }

    public function test_idempotencia_rodar_duas_vezes_nao_duplica_nada(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            contatos: [$this->contatoRow()],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => '000123', 'CODCCUSTO' => '01.001']],
            centrosCusto: [['CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => 'Administração', 'CODREDUZIDO' => null, 'CODCLASSIFICA' => null, 'ATIVO' => 1, 'PERMITELANC' => 1, 'RESPONSAVEL' => null]],
        );

        $service = $this->service($reader);
        $service->run($this->importOptions());
        $second = $service->run($this->importOptions());

        $this->assertSame(0, $second->clientsCriados);
        $this->assertSame(1, $second->clientsPuladosExistentes);
        $this->assertSame(0, $second->contatosCriados);
        $this->assertSame(1, $second->contatosPuladosEmail);
        $this->assertSame(0, $second->enderecosCriados);
        $this->assertSame(0, $second->centrosCustoCriados);
        $this->assertSame(0, $second->backfillCentroCusto);

        $this->assertSame(1, DB::table('clients')->count());
        $this->assertSame(1, DB::table('client_contatos')->count());
        $this->assertSame(2, DB::table('client_enderecos')->count());
        $this->assertSame(1, DB::table('centros_custo')->count());
    }

    public function test_dry_run_nao_grava_nada_e_relata_igual_a_execucao_real(): void
    {
        $make = fn (): FakeRmReader => new FakeRmReader(
            fcfo: [
                $this->fcfoRow(['CODCFO' => 'A1']),
                $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '12345678000195']), // duplicado no RM
            ],
            contatos: [$this->contatoRow(['CODCFO' => 'A1'])],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => 'A1', 'CODCCUSTO' => '01.001']],
            centrosCusto: [['CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => 'Administração', 'CODREDUZIDO' => null, 'CODCLASSIFICA' => null, 'ATIVO' => 1, 'PERMITELANC' => 1, 'RESPONSAVEL' => null]],
        );

        $dry = $this->service($make())->run($this->importOptions(dryRun: true));

        $this->assertSame(0, DB::table('clients')->count());
        $this->assertSame(0, DB::table('client_contatos')->count());
        $this->assertSame(0, DB::table('client_enderecos')->count());
        $this->assertSame(0, DB::table('centros_custo')->count());

        $real = $this->service($make())->run($this->importOptions());

        $this->assertSame($real->clientsCriados, $dry->clientsCriados);
        $this->assertSame($real->duplicadosNoRm, $dry->duplicadosNoRm);
        $this->assertSame($real->contatosCriados, $dry->contatosCriados);
        $this->assertSame($real->enderecosCriados, $dry->enderecosCriados);
        $this->assertSame($real->centrosCustoCriados, $dry->centrosCustoCriados);
        $this->assertSame(1, DB::table('clients')->count());
    }

    public function test_cliente_existente_ganha_linha_de_centro_custo_sem_ser_alterado(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'MANTEM', 'document' => '12.345.678/0001-95',
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => '000123', 'CODCCUSTO' => '01.001']],
            centrosCusto: [['CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => 'Administração', 'CODREDUZIDO' => null, 'CODCLASSIFICA' => null, 'ATIVO' => 1, 'PERMITELANC' => 1, 'RESPONSAVEL' => null]],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->backfillCentroCusto);
        $cc = DB::table('centros_custo')->where('client_id', $clientId)->first();
        $this->assertNotNull($cc);
        $this->assertSame('01.001', $cc->codigo);

        // O cliente em si permanece intocado.
        $row = DB::table('clients')->first();
        $this->assertSame('MANTEM', $row->name);
        $this->assertSame('2020-01-01 00:00:00', (string) $row->updated_at);
        $this->assertSame(0, DB::table('client_audit_logs')->count());
    }

    public function test_backfill_desligado_nao_cria_centro_custo_para_existente(): void
    {
        DB::table('clients')->insert([
            'name' => 'MANTEM', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => '000123', 'CODCCUSTO' => '01.001']],
            centrosCusto: [['CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => 'Administração', 'CODREDUZIDO' => null, 'CODCLASSIFICA' => null, 'ATIVO' => 1, 'PERMITELANC' => 1, 'RESPONSAVEL' => null]],
        );

        $report = $this->service($reader)->run($this->importOptions(backfill: false));

        $this->assertSame(0, $report->backfillCentroCusto);
        $this->assertSame(0, DB::table('centros_custo')->count());
    }

    public function test_centro_custo_por_cliente_nao_duplica_em_reexecucao(): void
    {
        $make = fn (string $nome): FakeRmReader => new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            defaults: [['CODCOLIGADA' => 1, 'CODCOLCFO' => 1, 'CODCFO' => '000123', 'CODCCUSTO' => '01.001']],
            centrosCusto: [['CODCOLIGADA' => 1, 'CODCCUSTO' => '01.001', 'NOME' => $nome, 'CODREDUZIDO' => null, 'CODCLASSIFICA' => null, 'ATIVO' => 1, 'PERMITELANC' => 1, 'RESPONSAVEL' => null]],
        );

        $this->service($make('Administração'))->run($this->importOptions());
        $this->service($make('Administração Geral'))->run($this->importOptions());

        // O par (client_id, codigo) já existe: nada é duplicado nem sobrescrito.
        $this->assertSame(1, DB::table('centros_custo')->count());
        $this->assertSame('Administração', DB::table('centros_custo')->value('nome'));
    }

    public function test_endereco_parcial_preenche_string_vazia_nas_colunas_not_null(): void
    {
        // Só CEP preenchido: as demais colunas de client_enderecos são NOT NULL
        // no banco real e não podem receber null.
        $reader = new FakeRmReader(fcfo: [$this->fcfoRow([
            'RUA' => null, 'NUMERO' => null, 'COMPLEMENTO' => null, 'BAIRRO' => null,
            'CIDADE' => null, 'CODETD' => null, 'PAIS' => null, 'CODMUNICIPIO' => null,
            'RUAPGTO' => null, 'CEPPGTO' => null, 'CIDADEPGTO' => null,
        ])]);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(0, $report->erros);
        $this->assertSame(1, $report->enderecosCriados);

        $endereco = DB::table('client_enderecos')->firstOrFail();
        $this->assertSame('01310-100', $endereco->cep);
        foreach (['rua', 'numero', 'bairro', 'pais', 'estado', 'cod_ibge', 'municipio'] as $col) {
            $this->assertSame('', $endereco->{$col}, "coluna {$col} deveria ser string vazia");
        }
        $this->assertNull($endereco->complemento); // única nullable além de tipo
    }

    public function test_status_vira_booleano_e_respeita_o_default_quando_o_rm_nao_informa(): void
    {
        $reader = new FakeRmReader(fcfo: [
            $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95', 'ATIVO' => 0]),
            $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '04.124.922/0001-61', 'ATIVO' => null]),
        ]);

        $this->service($reader)->run($this->importOptions());

        $inativo = Client::query()->where('document', '12.345.678/0001-95')->firstOrFail();
        $this->assertFalse($inativo->status);
        $this->assertSame(0, (int) DB::table('clients')->where('id', $inativo->id)->value('status'));

        // ATIVO ausente no RM: a coluna NOT NULL cai no default do banco (1).
        $semInfo = Client::query()->where('document', '04.124.922/0001-61')->firstOrFail();
        $this->assertTrue($semInfo->status);
    }

    public function test_campos_complementares_do_contato_vao_para_obs(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            contatos: [$this->contatoRow(['IDCONTATO' => 7])],
            complColumns: ['CARGOCOMPL'],
            compl: ['1|000123|7' => ['CODCOLIGADA' => 1, 'CODCFO' => '000123', 'IDCONTATO' => 7, 'CARGOCOMPL' => 'Diretor']],
        );

        $this->service($reader)->run($this->importOptions());

        $obs = DB::table('client_contatos')->value('obs');
        $this->assertStringContainsString('Contato principal', (string) $obs);
        $this->assertStringContainsString('CARGOCOMPL: Diretor', (string) $obs);
    }
}
