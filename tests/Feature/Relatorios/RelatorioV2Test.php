<?php

namespace Tests\Feature\Relatorios;

use App\Models\Client;
use App\Models\ClientContato;
use App\Models\ClientEndereco;
use App\Models\Lista\Regional;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\BancoEmMemoria;
use Tests\TestCase;

/**
 * Relatórios v2: a base inteira em Excel — clientes com todos os campos e
 * contatos com a empresa a que pertencem.
 */
class RelatorioV2Test extends TestCase
{
    use BancoEmMemoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(string $role = 'Administrador', array $permissoes = []): User
    {
        static $sequencia = 0;
        $sequencia++;

        $user = User::create([
            'name' => "Usuário {$sequencia}",
            'email' => "relatoriov2{$sequencia}@teste.local",
            'password' => 'senha-secreta',
            'status' => true,
        ]);
        $user->assignRole($role);
        $user->givePermissionTo($permissoes);

        return $user;
    }

    private function contato(Client $client, string $nome, array $extra = []): ClientContato
    {
        return ClientContato::create(['client_id' => $client->id, 'user_id' => 0, 'nome' => $nome] + $extra);
    }

    /** @return array{0:Worksheet,1:array<string,string>} aba e mapa título => letra da coluna */
    private function planilha(TestResponse $response): array
    {
        $response->assertOk();

        $arquivo = tempnam(sys_get_temp_dir(), 'rel').'.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();
        } finally {
            @unlink($arquivo);
        }

        $colunas = [];
        foreach ($aba->getRowIterator(6, 6)->current()->getCellIterator() as $celula) {
            if ($celula->getValue() !== null) {
                $colunas[(string) $celula->getValue()] = $celula->getColumn();
            }
        }

        return [$aba, $colunas];
    }

    public function test_telas_exigem_permissao_e_quem_so_visualiza_nao_baixa(): void
    {
        $this->actingAs($this->usuario('Consulta'))->get(route('relatorios.v2.clientes'))->assertForbidden();

        $this->actingAs($this->usuario('Consulta', ['relatorios.view']));
        $this->get(route('relatorios.v2.clientes'))->assertOk()->assertDontSee(route('relatorios.v2.clientes.excel'));
        $this->get(route('relatorios.v2.contatos'))->assertOk();
        $this->get(route('relatorios.v2.clientes.excel'))->assertForbidden();
        $this->get(route('relatorios.v2.contatos.excel'))->assertForbidden();
    }

    public function test_menu_aponta_para_os_relatorios_v2(): void
    {
        $this->actingAs($this->usuario())->get(route('dashboard'))->assertSee(route('relatorios.v2.clientes'));
    }

    public function test_excel_de_clientes_leva_todos_os_cadastros_e_campos(): void
    {
        $regional = Regional::create(['nome' => 'Sudeste', 'ativo' => true]);
        $ativo = Client::factory()->create(['name' => 'ACME CONSORCIOS', 'regional_id' => $regional->id, 'associado_abac' => true]);
        Client::factory()->inactive()->create(['name' => 'BETA INATIVA']);
        ClientEndereco::create([
            'client_id' => $ativo->id, 'tipo' => 'principal', 'cep' => '01310-100',
            'rua' => 'Rua A', 'numero' => '1', 'bairro' => 'Centro', 'pais' => 'Brasil',
            'cod_ibge' => '0000000', 'estado' => 'SP', 'municipio' => 'São Paulo',
        ]);

        [$aba, $colunas] = $this->planilha($this->actingAs($this->usuario())->get(route('relatorios.v2.clientes.excel')));

        $this->assertSame('A', $colunas['Razão social']);
        $this->assertSame('ACME CONSORCIOS', $aba->getCell('A7')->getValue());
        $this->assertSame('BETA INATIVA', $aba->getCell('A8')->getValue());
        $this->assertSame('Sudeste', $aba->getCell($colunas['Regional'].'7')->getValue());
        $this->assertSame('Sim', $aba->getCell($colunas['Associado ABAC'].'7')->getValue());
        $this->assertSame('SP', $aba->getCell($colunas['UF'].'7')->getValue());
        $this->assertSame('01310-100', $aba->getCell($colunas['CEP'].'7')->getValue());
        // Toda coluna da tabela vira coluna da planilha (+ o endereço).
        $this->assertGreaterThanOrEqual(count(Schema::getColumnListing('clients')), count($colunas));
        $this->assertStringContainsString('TOTAL DE CLIENTES: 2', $aba->getCell('A9')->getValue());
    }

    public function test_excel_de_contatos_traz_a_empresa_de_cada_um(): void
    {
        $acme = Client::factory()->create(['name' => 'ACME CONSORCIOS', 'document' => '01234567000189']);
        $beta = Client::factory()->create(['name' => 'BETA']);
        $this->contato($beta, 'Zélia');
        $this->contato($acme, 'Carlos', ['email' => 'carlos@acme.test', 'representante_legal' => true]);

        [$aba, $colunas] = $this->planilha($this->actingAs($this->usuario())->get(route('relatorios.v2.contatos.excel')));

        $this->assertSame('A', $colunas['Empresa (razão social)']);
        // Ordenado por empresa.
        $this->assertSame('ACME CONSORCIOS', $aba->getCell('A7')->getValue());
        $this->assertSame('Carlos', $aba->getCell($colunas['Contato'].'7')->getValue());
        $this->assertSame('01234567000189', $aba->getCell($colunas['CNPJ/CPF da empresa'].'7')->getValue());
        $this->assertSame('carlos@acme.test', $aba->getCell($colunas['E-mail'].'7')->getValue());
        $this->assertSame('Sim', $aba->getCell($colunas['Representante legal'].'7')->getValue());
        $this->assertSame('BETA', $aba->getCell('A8')->getValue());
        $this->assertSame('Zélia', $aba->getCell($colunas['Contato'].'8')->getValue());
        $this->assertStringContainsString('TOTAL DE CONTATOS: 2', $aba->getCell('A9')->getValue());
    }

    public function test_download_devolve_o_token_para_fechar_o_carregando(): void
    {
        $response = $this->actingAs($this->usuario())->get(route('relatorios.v2.contatos.excel', ['download_token' => '1729450000000']));
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'relatorio_pronto');

        $this->assertSame('1729450000000', $cookie?->getValue());
    }
}
