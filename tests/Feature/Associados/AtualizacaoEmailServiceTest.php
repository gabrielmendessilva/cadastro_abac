<?php

namespace Tests\Feature\Associados;

use App\Services\Associados\AtualizacaoEmailOptions;
use App\Services\Associados\AtualizacaoEmailService;
use App\Services\Associados\Exceptions\AssociadosSyncException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AtualizacaoEmailServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.pgsql-associado' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);

        config([
            'associados.connection' => 'pgsql-associado',
            'associados.cnpj_meta_like' => '%cnpj_associada%',
            // A conta-empresa é achada pela meta que aponta para `name`.
            'associados.meta_map' => ['_associada_razao_social' => 'name'],
        ]);

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function ($t) {
                $t->id();
                $t->string('name');
                $t->string('document', 20);
                $t->string('email')->nullable();
                $t->boolean('associado_abac')->default(false);
                $t->timestamps();
            });
        }

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

    private function service(): AtualizacaoEmailService
    {
        return new AtualizacaoEmailService(logger: new NullLogger);
    }

    private function wpUser(int $id, string $email): void
    {
        DB::connection('pgsql-associado')->table('wp_users')->insert([
            'ID' => $id, 'user_login' => "u{$id}", 'user_email' => $email, 'display_name' => "User {$id}",
        ]);
    }

    /** @param array<string,string> $metas */
    private function wpMetas(int $userId, array $metas): void
    {
        static $seq = 0;
        $linhas = [];
        foreach ($metas as $key => $value) {
            $linhas[] = ['umeta_id' => ++$seq + 10000, 'user_id' => $userId, 'meta_key' => $key, 'meta_value' => $value];
        }
        DB::connection('pgsql-associado')->table('wp_usermeta')->insert($linhas);
    }

    private function empresa(int $userId, string $email, string $cnpj, string $razao = 'EMPRESA WP'): void
    {
        $this->wpUser($userId, $email);
        $this->wpMetas($userId, [
            '_associada_razao_social' => $razao,
            'cnpj_associada' => $cnpj,
        ]);
    }

    public function test_sobrescreve_o_email_do_cliente_com_o_da_empresa_no_wp(): void
    {
        $id = DB::table('clients')->insertGetId([
            'name' => 'ACME', 'document' => '12.345.678/0001-95',
            'email' => 'antigo@acme.com', 'associado_abac' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(1, 'novo@acme.com.br', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->atualizados);
        $this->assertSame('novo@acme.com.br', DB::table('clients')->where('id', $id)->value('email'));
    }

    public function test_preenche_email_vazio(): void
    {
        DB::table('clients')->insert([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(1, 'contato@acme.com.br', '12345678000195'); // variante crua

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->atualizados);
        $this->assertSame('contato@acme.com.br', DB::table('clients')->first()->email);
        $this->assertSame('(vazio)', $report->mudancas[0]['de']);
    }

    public function test_email_igual_ignorando_caixa_nao_conta_como_mudanca(): void
    {
        DB::table('clients')->insert([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => 'Contato@ACME.com.br',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(1, 'contato@acme.com.br', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(0, $report->atualizados);
        $this->assertSame(1, $report->semMudanca);
        // Não reescreve: a caixa original é preservada.
        $this->assertSame('Contato@ACME.com.br', DB::table('clients')->first()->email);
    }

    public function test_dry_run_nao_grava_mas_conta(): void
    {
        DB::table('clients')->insert([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => 'antigo@acme.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(1, 'novo@acme.com.br', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions(dryRun: true));

        $this->assertSame(1, $report->atualizados);
        $this->assertSame('antigo@acme.com', DB::table('clients')->first()->email, 'dry-run não podia gravar');
    }

    public function test_email_de_origem_invalido_nao_sobrescreve(): void
    {
        DB::table('clients')->insert([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => 'bom@acme.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(1, 'nao-e-email', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->emailInvalido);
        $this->assertSame(0, $report->atualizados);
        $this->assertSame('bom@acme.com', DB::table('clients')->first()->email);
    }

    public function test_cnpj_sem_cliente_no_destino_e_pulado(): void
    {
        $this->empresa(1, 'novo@acme.com.br', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->semCliente);
        $this->assertSame(0, $report->atualizados);
        $this->assertSame(0, DB::table('clients')->count());
    }

    public function test_contato_pessoa_nao_e_confundido_com_a_empresa(): void
    {
        $id = DB::table('clients')->insertGetId([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => 'antigo@acme.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Empresa (user 5) e uma pessoa do mesmo CNPJ (user 2), sem razão social.
        $this->empresa(5, 'empresa@acme.com.br', '12.345.678/0001-95');
        $this->wpUser(2, 'joao.pessoa@gmail.com');
        $this->wpMetas(2, ['cnpj_associada' => '12.345.678/0001-95', 'first_name' => 'João']);

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->atualizados);
        // Vence o e-mail da conta-empresa, não o da pessoa.
        $this->assertSame('empresa@acme.com.br', DB::table('clients')->where('id', $id)->value('email'));
    }

    public function test_mesmo_cnpj_com_duas_contas_empresa_usa_o_menor_user_id(): void
    {
        $id = DB::table('clients')->insertGetId([
            'name' => 'ACME', 'document' => '12.345.678/0001-95', 'email' => 'antigo@acme.com',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->empresa(9, 'nove@acme.com.br', '12.345.678/0001-95');
        $this->empresa(4, 'quatro@acme.com.br', '12.345.678/0001-95');

        $report = $this->service()->run(new AtualizacaoEmailOptions);

        $this->assertSame(1, $report->cnpjDuplicado);
        $this->assertSame(1, $report->atualizados);
        $this->assertSame('quatro@acme.com.br', DB::table('clients')->where('id', $id)->value('email'));
    }

    public function test_meta_map_sem_name_falha_com_mensagem_clara(): void
    {
        config(['associados.meta_map' => ['telefone_empresa' => 'phone']]);

        $this->expectException(AssociadosSyncException::class);
        $this->expectExceptionMessageMatches('/name/');

        $this->service()->run(new AtualizacaoEmailOptions);
    }
}
