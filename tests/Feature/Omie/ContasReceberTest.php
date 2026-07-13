<?php

namespace Tests\Feature\Omie;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContasReceberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        // Tabela mínima só para o teste de lookup — evita rodar migrations MySQL-specific
        // do projeto (que usam ALTER TABLE ... MODIFY, incompatível com sqlite).
        if (!Schema::hasTable('clients')) {
            Schema::create('clients', function ($t) {
                $t->id();
                $t->string('cpf_cnpj', 20)->nullable();
                $t->string('nome', 255)->nullable();
            });
        }
    }

    public function test_create_envia_payload_correto_para_omie_e_devolve_data(): void
    {
        Http::fake([
            'app.omie.com.br/*' => Http::response([
                'codigo_lancamento_omie' => 123456789,
                'codigo_lancamento_integracao' => 'TESTE-001',
                'codigo_status' => '0',
                'descricao_status' => 'OK',
            ], 200),
        ]);

        $payload = [
            'codigo_lancamento_integracao' => 'TESTE-001',
            'codRm'         => 999,
            'vencimento'    => '31/12/2026',
            'valor'         => 150.75,
            'cc_id'         => 1,
            'user_id'       => 'user-abc',
            'categoria'     => '1.01.02',
            'tipo_receber'  => 'BOL',
            'projeto'       => 42,
            'observacao'    => 'teste feature',
            'idCompra'      => 'PED-77',
            'desconto'      => 3.50,
        ];

        $response = $this->postJson('/api/omie/lancamentos/contas-receber/create', $payload);

        $response->assertStatus(201)
            ->assertJson(['ok' => true])
            ->assertJsonPath('data.codigo_lancamento_omie', 123456789);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://app.omie.com.br/api/v1/financas/contareceber/'
                && $body['call']       === 'IncluirContaReceber'
                && $body['app_key']    === 'fake_key'
                && $body['app_secret'] === 'fake_secret'
                && $body['param'][0]['codigo_lancamento_integracao'] === 'TESTE-001'
                && $body['param'][0]['codigo_cliente_fornecedor']     === 999
                && $body['param'][0]['data_vencimento']               === '31/12/2026'
                && $body['param'][0]['valor_documento']               === 150.75
                && $body['param'][0]['id_conta_corrente']             === 1
                && $body['param'][0]['codigo_categoria']              === '1.01.02'
                // hack fiscal preservado: desconto vira valor_pis + retem_pis
                && $body['param'][0]['valor_pis']                     === 3.5
                && $body['param'][0]['retem_pis']                     === 'S'
                // hack: tipo_receber é replicado em numero_documento_fiscal e numero_documento
                && $body['param'][0]['numero_documento_fiscal']       === 'BOL'
                && $body['param'][0]['numero_documento']              === 'BOL';
        });
    }

    public function test_faultstring_da_omie_vira_http_502(): void
    {
        Http::fake([
            'app.omie.com.br/*' => Http::response([
                'faultstring' => 'Cliente/Fornecedor não encontrado',
                'faultcode'   => 'SOAP-ENV:Client-101',
            ], 200),
        ]);

        $payload = [
            'codigo_lancamento_integracao' => 'TESTE-002',
            'codRm'         => 1,
            'vencimento'    => '31/12/2026',
            'valor'         => 10,
            'cc_id'         => 1,
            'user_id'       => 'x',
            'categoria'     => 'x',
            'tipo_receber'  => 'BOL',
        ];

        $response = $this->postJson('/api/omie/lancamentos/contas-receber/create', $payload);

        $response->assertStatus(502)
            ->assertJson(['ok' => false])
            ->assertJsonPath('omie.faultcode', 'SOAP-ENV:Client-101')
            ->assertJsonPath('omie.faultstring', 'Cliente/Fornecedor não encontrado');
    }

    public function test_erro_de_validacao_retorna_422_sem_bater_na_omie(): void
    {
        Http::fake();

        $response = $this->postJson('/api/omie/lancamentos/contas-receber/create', [
            // faltando quase tudo
            'valor' => -1,
        ]);

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_contas_pagar_stub_retorna_501(): void
    {
        Http::fake();
        $this->postJson('/api/omie/lancamentos/contas-pagar/create', ['foo' => 'bar'])
            ->assertStatus(501);
        $this->postJson('/api/omie/lancamentos/contas-pagar/edit', ['foo' => 'bar'])
            ->assertStatus(501);
        $this->getJson('/api/omie/lancamentos/contas-pagar/find')
            ->assertStatus(501);
    }

    public function test_boletos_stub_retorna_501(): void
    {
        Http::fake();
        $this->postJson('/api/omie/boletos/contas-receber/create', [])->assertStatus(501);
        $this->postJson('/api/omie/boletos/contas-receber/edit', [])->assertStatus(501);
        $this->postJson('/api/omie/boletos/contas-receber/cancel', [])->assertStatus(501);
    }

    public function test_users_find_sem_documento_retorna_422(): void
    {
        $this->getJson('/api/users/find')->assertStatus(422);
    }

    public function test_users_find_com_documento_inexistente_retorna_vazio(): void
    {
        $response = $this->getJson('/api/users/find?document=99999999999999');
        $response->assertStatus(200);
        // Sem client encontrado: JSON body vem null (ou array vazio dependendo do serializer)
        $this->assertContains($response->json(), [null, []]);
    }
}
