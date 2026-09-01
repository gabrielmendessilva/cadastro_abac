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
                $t->string('num_filiacao_abac', 50)->nullable();
                $t->date('dt_filiacao_abac')->nullable();
                $t->string('num_filiacao_sinac', 50)->nullable();
                $t->date('dt_filiacao_sinac')->nullable();
                // NOT NULL default 0 no banco vivo, como associado_sinac.
                $t->boolean('associado_abac')->default(false);
                $t->string('categoria', 100)->nullable();
                $t->string('situacao_abac', 100)->nullable();
                $t->string('ocorrencia_abac', 20)->nullable();
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
                $t->string('aniversario', 5)->nullable();
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

        if (! Schema::hasTable('comites')) {
            Schema::create('comites', function ($t) {
                $t->id();
                $t->string('nome', 150);
                $t->string('descricao', 500)->nullable();
                $t->boolean('ativo')->default(true);
                $t->timestamps();
            });

            // Mesmo vocabulário oficial do banco vivo (ListasDominioSeeder).
            foreach ([
                'Comitê Antifraudes', 'Comitê Compliance e Auditoria Interna', 'Comitê Contábil',
                'Comitê Crédito e Cobrança', 'Comitê Estudos Econômicos', 'Comitê Gestão de Grupos',
                'Comitê Gestão de Pessoas', 'Comitê Inovação', 'Comitê Internacional', 'Comitê Jurídico',
                'Comitê Marketing Institucional', 'Comitê Ouvidoria', 'Comitê Política de Parceiros',
                'Comitê Tecnologia da Informação',
            ] as $nome) {
                DB::table('comites')->insert(['nome' => $nome, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        if (! Schema::hasTable('client_comites')) {
            Schema::create('client_comites', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id');
                $t->unsignedBigInteger('contato_id')->nullable();
                $t->string('comite_nome');
                $t->string('papel')->default('titular');
                $t->text('observacoes')->nullable();
                $t->timestamps();
                // Mesma trava do banco vivo (migration 2026_08_10_000020).
                $t->unique(['client_id', 'contato_id', 'comite_nome']);
            });
        }

        if (! Schema::hasTable('client_redes_sociais')) {
            Schema::create('client_redes_sociais', function ($t) {
                $t->id();
                $t->unsignedBigInteger('client_id');
                $t->string('tipo');
                $t->string('rotulo', 100)->nullable();
                $t->string('url', 500);
                $t->timestamps();
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

    private function importOptions(
        bool $dryRun = false,
        bool $backfill = true,
        int $chunk = 2,
        bool $desativarForaDeOrdem = true,
    ): RmImportOptions {
        return new RmImportOptions(
            dryRun: $dryRun,
            chunkSize: $chunk,
            backfill: $backfill,
            desativarForaDeOrdem: $desativarForaDeOrdem,
        );
    }

    /**
     * Linhas de FCFOCOMPL com o par STATUS/OCORRENCIA em ordem — o recorte que
     * mantém a empresa ativa no app. Chaves no formato "coligada|codcfo".
     *
     * @param list<string> $chaves
     * @return array<string,array<string,mixed>>
     */
    private function fcfoComplEmOrdem(array $chaves): array
    {
        $linhas = [];

        foreach ($chaves as $chave) {
            [$coligada, $codcfo] = explode('|', $chave);

            $linhas[$chave] = [
                'CODCOLIGADA' => (int) $coligada,
                'CODCFO' => $codcfo,
                'STATUS' => 'OK',
                'OCORRENCIA' => 'OK',
            ];
        }

        return $linhas;
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
            // Aba "Opcionais" do RM: site, filiação ABAC/SINAC e data de abertura.
            'CAMPOALFAOP1' => 'www.empresateste.com.br',
            'CAMPOALFAOP2' => '288',
            'DATAOP2' => '1988-07-15 00:00:00.000',
            'CAMPOALFAOP3' => '358',
            'DATAOP3' => '1989-03-01 00:00:00.000',
            'DATAOP1' => '1987-10-28 00:00:00.000',
            // Tipo de cli/for (FTCF) — vira clients.categoria.
            'CODCOLTCF' => 1,
            'CODTCF' => 'CAT ESPECIAL',
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
            fcfoCompl: $this->fcfoComplEmOrdem(['1|000123']),
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->clientsCriados);
        $this->assertSame(2, $report->enderecosCriados);
        $this->assertSame(1, $report->contatosCriados);
        $this->assertSame(1, $report->centrosCustoCriados);
        $this->assertSame(1, $report->redesSociaisCriadas);
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
        $this->assertSame('Consórcios', $client->area_atuacao);

        // Campos da aba "Opcionais" do RM.
        $this->assertSame('288', $client->num_filiacao_abac);
        $this->assertSame('1988-07-15', $client->dt_filiacao_abac->format('Y-m-d'));
        $this->assertSame('358', $client->num_filiacao_sinac);
        $this->assertSame('1989-03-01', $client->dt_filiacao_sinac->format('Y-m-d'));
        // DATAOP1 prevalece sobre DTINICATIVIDADES (2010-05-10).
        $this->assertSame('1987-10-28', $client->dt_abertura_empresa->format('Y-m-d'));
        // Sem linha na FTCF, a categoria fica com o próprio código do tipo.
        $this->assertSame('CAT ESPECIAL', $client->categoria);
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

        // Site do RM vira rede social clicável (clients não tem coluna de site).
        $rede = DB::table('client_redes_sociais')->where('client_id', $client->id)->first();
        $this->assertNotNull($rede);
        $this->assertSame('site', $rede->tipo);
        $this->assertSame('https://www.empresateste.com.br', $rede->url);

        $contato = DB::table('client_contatos')->where('client_id', $client->id)->first();
        $this->assertNotNull($contato);
        $this->assertSame('maria@x.com', $contato->email);
        // FAX do RM é celular nesta base, não um segundo telefone.
        $this->assertSame('(11) 3333-3333', $contato->celular);
        $this->assertNull($contato->telefone_2);
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
        $this->assertSame(0, $second->redesSociaisCriadas);
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0], array_values($second->backfillCampos));

        $this->assertSame(1, DB::table('clients')->count());
        $this->assertSame(1, DB::table('client_contatos')->count());
        $this->assertSame(2, DB::table('client_enderecos')->count());
        $this->assertSame(1, DB::table('centros_custo')->count());
        $this->assertSame(1, DB::table('client_redes_sociais')->count());
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
        $this->assertSame(0, DB::table('client_redes_sociais')->count());

        $real = $this->service($make())->run($this->importOptions());

        $this->assertSame($real->clientsCriados, $dry->clientsCriados);
        $this->assertSame($real->duplicadosNoRm, $dry->duplicadosNoRm);
        $this->assertSame($real->contatosCriados, $dry->contatosCriados);
        $this->assertSame($real->enderecosCriados, $dry->enderecosCriados);
        $this->assertSame($real->centrosCustoCriados, $dry->centrosCustoCriados);
        $this->assertSame($real->redesSociaisCriadas, $dry->redesSociaisCriadas);
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

    /**
     * Os campos opcionais do RM são a única fonte de filiação ABAC/SINAC, data de
     * abertura e site — e a base já estava carregada quando eles foram mapeados.
     * Por isso eles entram também em cliente existente, mas só onde está vazio.
     */
    public function test_campos_opcionais_completam_cliente_existente_sem_sobrescrever(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'MANTEM',
            'document' => '12.345.678/0001-95',
            'num_filiacao_abac' => '999',            // já preenchido: o RM não pode mexer
            'dt_abertura_empresa' => '2000-01-01',   // idem
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions());

        $this->assertSame(1, $report->clientsPuladosExistentes);
        $this->assertSame([
            'num_filiacao_abac' => 0,
            'dt_filiacao_abac' => 1,
            'num_filiacao_sinac' => 1,
            'dt_filiacao_sinac' => 1,
            'dt_abertura_empresa' => 0,
            'categoria' => 1,
            'situacao_abac' => 0,
            'ocorrencia_abac' => 0,
        ], $report->backfillCampos);

        $row = DB::table('clients')->where('id', $clientId)->first();
        $this->assertSame('999', $row->num_filiacao_abac);
        $this->assertStringStartsWith('2000-01-01', (string) $row->dt_abertura_empresa);
        $this->assertStringStartsWith('1988-07-15', (string) $row->dt_filiacao_abac);
        $this->assertSame('358', $row->num_filiacao_sinac);
        $this->assertStringStartsWith('1989-03-01', (string) $row->dt_filiacao_sinac);

        // Backfill não é edição de usuário: sem auditoria e sem tocar no updated_at.
        $this->assertSame('MANTEM', $row->name);
        $this->assertSame('2020-01-01 00:00:00', (string) $row->updated_at);
        $this->assertSame(0, DB::table('client_audit_logs')->count());

        // O site do RM entra como rede social do cliente existente.
        $this->assertSame(1, $report->redesSociaisCriadas);
        $this->assertSame(
            'https://www.empresateste.com.br',
            DB::table('client_redes_sociais')->where('client_id', $clientId)->value('url'),
        );
    }

    /**
     * O RM não é fonte de quem é associado ABAC: essa informação vem do legado
     * (clients:backfill-legado) e do WordPress dos associados (associados:sync).
     * A carga do RM não pode mexer em `associado_abac` em hipótese nenhuma.
     */
    public function test_import_do_rm_nunca_altera_associado_abac(): void
    {
        $associada = DB::table('clients')->insertGetId([
            'name' => 'ASSOCIADA', 'document' => '12.345.678/0001-95', 'associado_abac' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $naoAssociada = DB::table('clients')->insertGetId([
            'name' => 'NAO ASSOCIADA', 'document' => '04.124.922/0001-61', 'associado_abac' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(fcfo: [
            $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95']),
            $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '04.124.922/0001-61']),
            $this->fcfoRow(['CODCFO' => 'A3', 'CGCCFO' => '52.568.821/0001-22']), // cliente novo
        ]);

        $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, (int) DB::table('clients')->where('id', $associada)->value('associado_abac'));
        $this->assertSame(0, (int) DB::table('clients')->where('id', $naoAssociada)->value('associado_abac'));

        // Cliente criado pela carga também não vem marcado como associado.
        $this->assertSame(
            0,
            (int) DB::table('clients')->where('document', '52.568.821/0001-22')->value('associado_abac'),
        );
    }

    public function test_backfill_desligado_nao_toca_nos_campos_opcionais_nem_no_site(): void
    {
        DB::table('clients')->insert([
            'name' => 'MANTEM', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions(backfill: false));

        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0], array_values($report->backfillCampos));
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0], array_values($report->backfillContato));
        $this->assertSame(0, $report->redesSociaisCriadas);
        $this->assertNull(DB::table('clients')->value('num_filiacao_abac'));
        $this->assertSame(0, DB::table('client_redes_sociais')->count());
    }

    public function test_cliente_que_ja_tem_site_nao_ganha_um_segundo(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'MANTEM', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_redes_sociais')->insert([
            'client_id' => $clientId, 'tipo' => 'site', 'rotulo' => null,
            'url' => 'https://outro-endereco.com.br',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions());

        $this->assertSame(0, $report->redesSociaisCriadas);
        $this->assertSame(1, DB::table('client_redes_sociais')->count());
        $this->assertSame('https://outro-endereco.com.br', DB::table('client_redes_sociais')->value('url'));
    }

    /**
     * O CAMPOALFAOP1 é campo livre: no RM real há telefone e texto solto no meio
     * dos endereços. O destino é um link clicável — lixo vira warning, não link.
     */
    public function test_campoalfaop1_que_nao_e_endereco_nao_vira_rede_social(): void
    {
        $reader = new FakeRmReader(fcfo: [
            $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95', 'CAMPOALFAOP1' => '(11) 3257-4182 - 8 and.']),
            $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '04.124.922/0001-61', 'CAMPOALFAOP1' => 'http://www.comesquema.com.br/']),
        ]);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->sitesInvalidos);
        $this->assertSame(1, $report->redesSociaisCriadas);
        $this->assertSame(
            ['http://www.comesquema.com.br/'],
            DB::table('client_redes_sociais')->pluck('url')->all(),
        );
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
        // Os dois com o cadastro em ordem na FCFOCOMPL: aqui quem decide o status
        // é só o ATIVO da FCFO.
        $reader = new FakeRmReader(
            fcfo: [
                $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95', 'ATIVO' => 0]),
                $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '04.124.922/0001-61', 'ATIVO' => null]),
            ],
            fcfoCompl: $this->fcfoComplEmOrdem(['1|A1', '1|A2']),
        );

        $this->service($reader)->run($this->importOptions());

        $inativo = Client::query()->where('document', '12.345.678/0001-95')->firstOrFail();
        $this->assertFalse($inativo->status);
        $this->assertSame(0, (int) DB::table('clients')->where('id', $inativo->id)->value('status'));

        // ATIVO ausente no RM: a coluna NOT NULL cai no default do banco (1).
        $semInfo = Client::query()->where('document', '04.124.922/0001-61')->firstOrFail();
        $this->assertTrue($semInfo->status);
    }

    /** @param array<string,mixed> $compl */
    private function readerComCompl(array $compl, array $contatoOverrides = []): FakeRmReader
    {
        return new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            contatos: [$this->contatoRow($contatoOverrides + ['IDCONTATO' => 7])],
            complColumns: ['DEPTO', 'ANIV', 'OUTROS', 'REPRESENTANTE', 'COMITE'],
            compl: ['1|000123|7' => ['CODCOLIGADA' => 1, 'CODCFO' => '000123', 'IDCONTATO' => 7] + $compl],
        );
    }

    public function test_campos_do_contato_vao_para_colunas_proprias(): void
    {
        $reader = $this->readerComCompl([
            'DEPTO' => 'DIRETORIA',
            'ANIV' => null,
            'OUTROS' => 'ADMINISTRATIVO, INFORMATICA',
            'REPRESENTANTE' => 'S',
            'COMITE' => 'JURÍDICO',
        ], ['OBSERVACAO' => '16/09']);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->contatosCriados);

        $contato = DB::table('client_contatos')->firstOrFail();
        $this->assertSame('DIRETORIA', $contato->departamento);
        $this->assertSame('ADMINISTRATIVO, INFORMATICA', $contato->outro_departamento);
        $this->assertSame('16/09', $contato->aniversario);
        $this->assertSame('(11) 3333-3333', $contato->celular);
        $this->assertSame(1, (int) $contato->representante_legal);
        $this->assertSame(1, (int) $contato->comite);
        // O aniversário saiu do texto livre: obs não repete o "16/09".
        $this->assertStringNotContainsString('16/09', (string) $contato->obs);
        // Campos com coluna própria também não são mais despejados no obs.
        $this->assertStringNotContainsString('DEPTO:', (string) $contato->obs);

        // Comitê vira vínculo em client_comites, com o nome oficial da lista.
        $this->assertSame(1, $report->comitesCriados);
        $comite = DB::table('client_comites')->firstOrFail();
        $this->assertSame('Comitê Jurídico', $comite->comite_nome);
        $this->assertSame($contato->id, $comite->contato_id);
        $this->assertSame('titular', $comite->papel);
    }

    /**
     * O RM escreve o nome do comitê à mão: grafias, acentos e plural variam, e
     * mais de um vem separado por "/". A lista de domínio do app é o vocabulário.
     */
    public function test_nomes_de_comite_do_rm_casam_com_a_lista_de_dominio(): void
    {
        $reader = $this->readerComCompl(['COMITE' => 'ANTIFRAUDE/CREDITO E COBRANCA/MARKETING/COMITE CONTABIL/XPTO']);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(5, $report->comitesCriados);
        $this->assertSame([
            'Comitê Antifraudes',           // plural tolerado
            'Comitê Crédito e Cobrança',    // acento resolvido
            'Comitê Marketing Institucional', // prefixo único
            'Comitê Contábil',              // prefixo "COMITE " removido
            'XPTO',                         // fora da lista: mantém o nome do RM
        ], DB::table('client_comites')->orderBy('id')->pluck('comite_nome')->all());
    }

    /** O RM escreve o papel junto do nome quando é coordenação. */
    public function test_coordenadora_no_nome_vira_papel_do_comite(): void
    {
        $reader = $this->readerComCompl(['COMITE' => 'COORDENADORA COMITE ANTIFRAUDES/OUVIDORIA']);

        $this->service($reader)->run($this->importOptions());

        $vinculos = DB::table('client_comites')->orderBy('id')->get(['comite_nome', 'papel']);
        $this->assertSame('Comitê Antifraudes', $vinculos[0]->comite_nome);
        $this->assertSame('coordenador', $vinculos[0]->papel);
        $this->assertSame('Comitê Ouvidoria', $vinculos[1]->comite_nome);
        $this->assertSame('titular', $vinculos[1]->papel);
    }

    /** Quando COMITE está vazio, o mesmo dado costuma estar em OUTROS. */
    public function test_outros_com_nome_de_comite_nao_vira_departamento(): void
    {
        $reader = $this->readerComCompl(['COMITE' => null, 'OUTROS' => 'COMITÊ OUVIDORIA']);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->comitesCriados);
        $this->assertSame('Comitê Ouvidoria', DB::table('client_comites')->value('comite_nome'));
        $this->assertNull(DB::table('client_contatos')->value('outro_departamento'));
        $this->assertSame(1, (int) DB::table('client_contatos')->value('comite'));
    }

    /**
     * Todos os contatos da base já existem (a carga original os criou), então sem
     * backfill de contato nada destes campos novos chegaria ao app.
     */
    public function test_contato_existente_recebe_campos_novos_sem_perder_o_que_ja_tinha(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'CLIENTE', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $contatoId = DB::table('client_contatos')->insertGetId([
            'client_id' => $clientId,
            'nome' => 'Maria Souza',
            'email' => 'maria@x.com',
            'departamento' => 'JA PREENCHIDO',   // não pode ser sobrescrito
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);

        $reader = $this->readerComCompl([
            'DEPTO' => 'DIRETORIA',
            'OUTROS' => null,
            'REPRESENTANTE' => 'S',
            'COMITE' => 'OUVIDORIA',
        ], ['OBSERVACAO' => '16/09']);

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(0, $report->contatosCriados);
        $this->assertSame(1, $report->contatosPuladosEmail);
        $this->assertSame(0, $report->backfillContato['departamento']);
        $this->assertSame(1, $report->backfillContato['funcao']);
        $this->assertSame(1, $report->backfillContato['celular']);
        $this->assertSame(1, $report->backfillContato['aniversario']);
        $this->assertSame(1, $report->backfillContato['representante_legal']);

        $contato = DB::table('client_contatos')->where('id', $contatoId)->first();
        $this->assertSame('JA PREENCHIDO', $contato->departamento);
        $this->assertSame('Financeiro', $contato->funcao);
        $this->assertSame('16/09', $contato->aniversario);
        $this->assertSame('(11) 3333-3333', $contato->celular);
        $this->assertStringStartsWith('1990-01-02', (string) $contato->dt_nascimento);
        $this->assertSame('Maria Souza', $contato->nome);
        // Backfill não é edição de usuário: nome, obs e updated_at intocados.
        $this->assertSame('2020-01-01 00:00:00', (string) $contato->updated_at);

        $this->assertSame(1, DB::table('client_comites')->where('contato_id', $contatoId)->count());
    }

    /**
     * A função é o campo mais editado à mão no CRUD — o backfill só completa a
     * que está vazia, igual às demais colunas alimentadas pelo RM.
     */
    public function test_funcao_preenchida_a_mao_nao_e_sobrescrita_pelo_rm(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'CLIENTE', 'document' => '12.345.678/0001-95',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('client_contatos')->insert([
            'client_id' => $clientId,
            'nome' => 'Maria Souza',
            'email' => 'maria@x.com',
            'funcao' => 'DIRETORA (digitado no CRUD)',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service($this->readerComCompl([]))->run($this->importOptions());

        $this->assertSame(0, $report->backfillContato['funcao']);
        $this->assertSame(
            'DIRETORA (digitado no CRUD)',
            DB::table('client_contatos')->where('email', 'maria@x.com')->value('funcao'),
        );
    }

    public function test_backfill_de_contato_nao_duplica_comite_em_reexecucao(): void
    {
        $make = fn (): FakeRmReader => $this->readerComCompl(['COMITE' => 'OUVIDORIA/INOVACAO']);

        $this->service($make())->run($this->importOptions());
        $segundo = $this->service($make())->run($this->importOptions());

        $this->assertSame(0, $segundo->comitesCriados);
        $this->assertSame(2, DB::table('client_comites')->count());
        $this->assertSame([0, 0, 0, 0, 0, 0, 0, 0], array_values($segundo->backfillContato));
    }

    /**
     * FCFOCOMPL.STATUS/OCORRENCIA são o recorte de "cadastro em ordem" que a
     * secretaria usava nos relatórios. São siglas sem dicionário na origem, então
     * entram como vieram — quem traduz é o negócio.
     */
    public function test_status_e_ocorrencia_da_fcfocompl_viram_situacao_e_ocorrencia_abac(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            fcfoCompl: ['1|000123' => ['CODCOLIGADA' => 1, 'CODCFO' => '000123', 'STATUS' => 'OK', 'OCORRENCIA' => 'CR']],
        );

        $this->service($reader)->run($this->importOptions());

        $client = Client::query()->firstOrFail();
        $this->assertSame('OK', $client->situacao_abac);
        $this->assertSame('CR', $client->ocorrencia_abac);
    }

    public function test_cliente_existente_sem_situacao_recebe_a_do_rm(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'MANTEM', 'document' => '12.345.678/0001-95',
            'ocorrencia_abac' => 'CA', // já preenchido: não pode ser sobrescrito
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            fcfoCompl: ['1|000123' => ['CODCOLIGADA' => 1, 'CODCFO' => '000123', 'STATUS' => 'OK', 'OCORRENCIA' => 'OK']],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->backfillCampos['situacao_abac']);
        $this->assertSame(0, $report->backfillCampos['ocorrencia_abac']);

        $row = DB::table('clients')->where('id', $clientId)->first();
        $this->assertSame('OK', $row->situacao_abac);
        $this->assertSame('CA', $row->ocorrencia_abac);
    }

    /**
     * A regra de status vinda do RM: quem está lá sem STATUS = 'OK' E
     * OCORRENCIA = 'OK' fica desativado no app, mesmo com ATIVO = 1 na FCFO.
     * Cli/for sem linha na FCFOCOMPL entra no mesmo saco.
     */
    public function test_cadastro_fora_de_ordem_no_rm_desativa_a_empresa(): void
    {
        $reader = new FakeRmReader(
            fcfo: [
                $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95']),
                $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '04.124.922/0001-61']),
                $this->fcfoRow(['CODCFO' => 'A3', 'CGCCFO' => '52.568.821/0001-22']),
            ],
            fcfoCompl: $this->fcfoComplEmOrdem(['1|A1']) + [
                // Ocorrência fora de 'OK' já basta para desativar.
                '1|A2' => ['CODCOLIGADA' => 1, 'CODCFO' => 'A2', 'STATUS' => 'OK', 'OCORRENCIA' => 'CR'],
                // A3 nem linha na FCFOCOMPL tem — cadastro também não está em ordem.
            ],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(2, $report->clientsDesativados);
        $this->assertSame(1, (int) DB::table('clients')->where('document', '12.345.678/0001-95')->value('status'));
        $this->assertSame(0, (int) DB::table('clients')->where('document', '04.124.922/0001-61')->value('status'));
        $this->assertSame(0, (int) DB::table('clients')->where('document', '52.568.821/0001-22')->value('status'));
    }

    /** O caixa/espaço da sigla varia na origem: 'ok', 'Ok ' e 'OK' valem o mesmo. */
    public function test_ok_do_rm_e_comparado_sem_caixa_e_sem_espaco(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            fcfoCompl: ['1|000123' => [
                'CODCOLIGADA' => 1, 'CODCFO' => '000123', 'STATUS' => ' ok ', 'OCORRENCIA' => 'Ok',
            ]],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(0, $report->clientsDesativados);
        $this->assertSame(1, (int) DB::table('clients')->value('status'));
    }

    /**
     * A desativação é a única escrita da carga em cliente já existente — e, como
     * o backfill, não é edição de usuário: sem auditoria e sem tocar no updated_at.
     */
    public function test_cliente_existente_fora_de_ordem_e_desativado_sem_auditoria(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'FORA DE ORDEM', 'document' => '12.345.678/0001-95', 'status' => true,
            'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00',
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            fcfoCompl: ['1|000123' => [
                'CODCOLIGADA' => 1, 'CODCFO' => '000123', 'STATUS' => 'FL', 'OCORRENCIA' => 'OK',
            ]],
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->clientsDesativados);

        $row = DB::table('clients')->where('id', $clientId)->first();
        $this->assertSame(0, (int) $row->status);
        $this->assertSame('2020-01-01 00:00:00', (string) $row->updated_at);
        $this->assertSame(0, DB::table('client_audit_logs')->count());
    }

    /** A regra só desativa: quem foi desativado à mão no app não volta pelo RM. */
    public function test_cadastro_em_ordem_nao_reativa_cliente_desativado_no_app(): void
    {
        DB::table('clients')->insert([
            'name' => 'DESATIVADA NO APP', 'document' => '12.345.678/0001-95', 'status' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow()],
            fcfoCompl: $this->fcfoComplEmOrdem(['1|000123']),
        );

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(0, $report->clientsDesativados);
        $this->assertSame(0, (int) DB::table('clients')->value('status'));
    }

    /** Só CNPJ que está no RM entra na regra — o resto da base não é tocado. */
    public function test_cliente_que_nao_esta_no_rm_continua_ativo(): void
    {
        DB::table('clients')->insert([
            'name' => 'SO NO APP', 'document' => '04.124.922/0001-61', 'status' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $reader = new FakeRmReader(fcfo: [$this->fcfoRow()]); // outro CNPJ, sem FCFOCOMPL

        $report = $this->service($reader)->run($this->importOptions());

        $this->assertSame(1, $report->clientsDesativados); // só o que veio do RM
        $this->assertSame(1, (int) DB::table('clients')->where('document', '04.124.922/0001-61')->value('status'));
    }

    /**
     * O mesmo CNPJ aparece em mais de uma linha do RM (coligadas/códigos
     * diferentes). Basta uma delas com o cadastro em ordem para a empresa
     * continuar ativa — inclusive quando a linha em ordem vem depois.
     */
    public function test_uma_linha_em_ordem_basta_quando_o_cnpj_se_repete_no_rm(): void
    {
        $reader = new FakeRmReader(
            fcfo: [
                $this->fcfoRow(['CODCFO' => 'A1', 'CGCCFO' => '12.345.678/0001-95']),
                $this->fcfoRow(['CODCFO' => 'A2', 'CGCCFO' => '12.345.678/0001-95']),
            ],
            fcfoCompl: $this->fcfoComplEmOrdem(['1|A2']) + [
                '1|A1' => ['CODCOLIGADA' => 1, 'CODCFO' => 'A1', 'STATUS' => 'CA', 'OCORRENCIA' => 'CA'],
            ],
        );

        $report = $this->service($reader)->run($this->importOptions(chunk: 1));

        $this->assertSame(1, $report->duplicadosNoRm);
        $this->assertSame(0, $report->clientsDesativados);
        $this->assertSame(1, (int) DB::table('clients')->value('status'));
    }

    public function test_dry_run_conta_a_desativacao_mas_nao_grava(): void
    {
        DB::table('clients')->insert([
            'name' => 'FORA DE ORDEM', 'document' => '12.345.678/0001-95', 'status' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions(dryRun: true));

        $this->assertSame(1, $report->clientsDesativados);
        $this->assertSame(1, (int) DB::table('clients')->value('status'));
    }

    /** --no-desativar: a carga roda sem mexer no status de ninguém. */
    public function test_opcao_desligada_deixa_o_status_como_esta(): void
    {
        DB::table('clients')->insert([
            'name' => 'FORA DE ORDEM', 'document' => '12.345.678/0001-95', 'status' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions(desativarForaDeOrdem: false));

        $this->assertSame(0, $report->clientsDesativados);
        $this->assertSame(1, (int) DB::table('clients')->value('status'));
    }

    /**
     * A desativação não é backfill: ela vale mesmo com --no-backfill, que só
     * governa o preenchimento de buracos em cadastro existente.
     */
    public function test_desativacao_independe_do_backfill(): void
    {
        DB::table('clients')->insert([
            'name' => 'FORA DE ORDEM', 'document' => '12.345.678/0001-95', 'status' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))
            ->run($this->importOptions(backfill: false));

        $this->assertSame(1, $report->clientsDesativados);
        $this->assertSame(0, (int) DB::table('clients')->value('status'));
    }

    /** A descrição da FTCF é o rótulo que vale; o código é só a chave. */
    public function test_categoria_usa_a_descricao_do_tipo_de_clifor(): void
    {
        $reader = new FakeRmReader(
            fcfo: [$this->fcfoRow(['CODCOLTCF' => 1, 'CODTCF' => 'ADM SEM AUTORIZACAO'])],
            tiposCliFor: [
                ['CODCOLIGADA' => 1, 'CODTCF' => 'ADM SEM AUTORIZACAO', 'DESCRICAO' => 'ADMINISTRADORA SEM AUTORIZACAO'],
            ],
        );

        $this->service($reader)->run($this->importOptions());

        $this->assertSame('ADMINISTRADORA SEM AUTORIZACAO', DB::table('clients')->value('categoria'));
    }

    /** A carga escreve em colunas de migration — falha com recado, não com SQL cru. */
    public function test_destino_sem_a_coluna_aniversario_falha_com_mensagem_clara(): void
    {
        Schema::table('client_contatos', fn ($t) => $t->dropColumn('aniversario'));

        try {
            $this->expectException(\App\Services\Rm\Exceptions\RmImportException::class);
            $this->expectExceptionMessageMatches('/aniversario.*migrate/s');

            $this->service(new FakeRmReader(fcfo: [$this->fcfoRow()]))->run($this->importOptions());
        } finally {
            Schema::table('client_contatos', fn ($t) => $t->string('aniversario', 5)->nullable());
        }
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
