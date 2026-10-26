<?php

namespace Tests\Feature\Relatorios;

use App\Models\Client;
use App\Models\ClientEndereco;
use App\Models\Lista\Regional;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\BancoEmMemoria;
use Tests\TestCase;

/**
 * Relatórios > Cadastro: a consulta "Administradoras por Regional Ajustáveis"
 * do Access virada em tela. O que importa: cada critério filtra o que deve,
 * e baixar o arquivo exige permissão própria.
 */
class RelatorioCadastroTest extends TestCase
{
    use BancoEmMemoria;

    protected function setUp(): void
    {
        parent::setUp();

        // Rotas comentadas em routes/web.php enquanto os relatórios v2 valem.
        $this->markTestSkipped('Relatório de cadastro desativado por enquanto.');

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(string $role = 'Administrador', array $permissoes = []): User
    {
        static $sequencia = 0;
        $sequencia++;

        $user = User::create([
            'name' => "Usuário {$sequencia}",
            'email' => "relatorio{$sequencia}@teste.local",
            'password' => 'senha-secreta',
            'status' => true,
        ]);
        $user->assignRole($role);
        $user->givePermissionTo($permissoes);

        return $user;
    }

    private function endereco(Client $client, string $uf, string $cidade): void
    {
        ClientEndereco::create([
            'client_id' => $client->id, 'tipo' => 'principal', 'cep' => '01310-100',
            'rua' => 'Rua A', 'numero' => '1', 'bairro' => 'Centro', 'pais' => 'Brasil',
            'cod_ibge' => '0000000', 'estado' => $uf, 'municipio' => $cidade,
        ]);
    }

    /** Razões sociais (coluna A) das linhas de dados da planilha baixada. */
    private function nomesDoExcel(array $parametros): array
    {
        $response = $this->get(route('relatorios.cadastro.excel', $parametros + ['aplicado' => '1', 'status' => '']));
        $response->assertOk();

        $arquivo = tempnam(sys_get_temp_dir(), 'rel').'.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();
            $nomes = [];
            // Linha 6 é o cabeçalho; a última é o total.
            for ($linha = 7; $linha < $aba->getHighestRow(); $linha++) {
                $nomes[] = $aba->getCell("A{$linha}")->getValue();
            }
            sort($nomes);

            return $nomes;
        } finally {
            @unlink($arquivo);
        }
    }

    private Regional $sudeste;

    private Regional $sul;

    private function base(): void
    {
        $this->sudeste = Regional::create(['nome' => 'Sudeste', 'ativo' => true]);
        $this->sul = Regional::create(['nome' => 'Sul I', 'ativo' => true]);

        $adm = Client::factory()->create(['name' => 'ADM SP ASSOCIADA', 'categoria' => 'ADMINISTRADORA', 'associado_abac' => true, 'regional_id' => $this->sudeste->id]);
        $this->endereco($adm, 'SP', 'São Paulo');

        $admPr = Client::factory()->create(['name' => 'ADM PR NAO ASSOCIADA', 'categoria' => 'ADMINISTRADORA', 'associado_abac' => false, 'regional_id' => $this->sul->id]);
        $this->endereco($admPr, 'PR', 'Curitiba');

        Client::factory()->create(['name' => 'CAT ESPECIAL', 'categoria' => 'CAT ESPECIAL', 'associado_abac' => true]);
        Client::factory()->create(['name' => 'FORNECEDOR', 'categoria' => 'FORNECEDOR']);
        Client::factory()->create(['name' => 'SEM CATEGORIA', 'categoria' => null]);
        Client::factory()->inactive()->create(['name' => 'ADM INATIVA', 'categoria' => 'ADMINISTRADORA']);
    }

    public function test_tela_exige_permissao_de_relatorios(): void
    {
        $this->actingAs($this->usuario('Consulta'))
            ->get(route('relatorios.cadastro'))
            ->assertForbidden();
    }

    public function test_quem_so_visualiza_ve_a_previa_mas_nao_baixa(): void
    {
        Client::factory()->create(['name' => 'ACME CONSORCIOS']);
        $user = $this->usuario('Consulta', ['relatorios.view']);

        $this->actingAs($user)->get(route('relatorios.cadastro'))
            ->assertOk()
            ->assertSee('ACME CONSORCIOS')
            ->assertDontSee(route('relatorios.cadastro.excel'));

        $this->get(route('relatorios.cadastro.excel'))->assertForbidden();
        $this->get(route('relatorios.cadastro.pdf'))->assertForbidden();
    }

    public function test_menu_mostra_relatorios_so_para_quem_tem_permissao(): void
    {
        $this->actingAs($this->usuario())->get(route('dashboard'))->assertSee(route('relatorios.cadastro'));
        $this->actingAs($this->usuario('Consulta'))->get(route('dashboard'))->assertDontSee(route('relatorios.cadastro'));
    }

    public function test_tela_abre_so_com_ativos(): void
    {
        $this->base();

        $this->actingAs($this->usuario())->get(route('relatorios.cadastro'))
            ->assertOk()
            ->assertSee('ADM SP ASSOCIADA')
            ->assertDontSee('ADM INATIVA');
    }

    public function test_filtra_por_tipo_de_cadastro(): void
    {
        $this->base();
        $this->actingAs($this->usuario());

        $this->assertSame(['ADM INATIVA', 'ADM PR NAO ASSOCIADA', 'ADM SP ASSOCIADA'], $this->nomesDoExcel(['tipo' => ['administradoras']]));
        $this->assertSame(['CAT ESPECIAL'], $this->nomesDoExcel(['tipo' => ['categorias_especiais']]));
        // "Não administradoras" inclui cadastro sem categoria (NOT IN descartaria NULL).
        $this->assertSame(['FORNECEDOR', 'SEM CATEGORIA'], $this->nomesDoExcel(['tipo' => ['demais']]));
        $this->assertSame(
            ['ADM INATIVA', 'ADM PR NAO ASSOCIADA', 'ADM SP ASSOCIADA', 'CAT ESPECIAL'],
            $this->nomesDoExcel(['tipo' => ['administradoras', 'categorias_especiais']]),
        );
    }

    public function test_filtra_por_associado_uf_e_regional(): void
    {
        $this->base();
        $this->actingAs($this->usuario());

        $this->assertSame(['ADM SP ASSOCIADA', 'CAT ESPECIAL'], $this->nomesDoExcel(['associado_abac' => '1']));
        $this->assertSame(['ADM PR NAO ASSOCIADA'], $this->nomesDoExcel(['tipo' => ['administradoras'], 'associado_abac' => '0', 'status' => '1']));
        $this->assertSame(['ADM PR NAO ASSOCIADA', 'ADM SP ASSOCIADA'], $this->nomesDoExcel(['uf' => ['SP', 'PR']]));
        $this->assertSame(['ADM PR NAO ASSOCIADA'], $this->nomesDoExcel(['regional' => (string) $this->sul->id]));
    }

    public function test_excel_leva_so_as_colunas_escolhidas(): void
    {
        $this->base();
        $this->actingAs($this->usuario());

        $response = $this->get(route('relatorios.cadastro.excel', [
            'aplicado' => '1', 'tipo' => ['administradoras'], 'uf' => ['SP'], 'colunas' => ['uf', 'razao_social', 'regional'],
        ]));

        $arquivo = tempnam(sys_get_temp_dir(), 'rel').'.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();

            // Ordem fixa do relatório, não a ordem dos parâmetros.
            $this->assertSame('Razão social', $aba->getCell('A6')->getValue());
            $this->assertSame('Regional', $aba->getCell('B6')->getValue());
            $this->assertSame('UF', $aba->getCell('C6')->getValue());
            $this->assertNull($aba->getCell('D6')->getValue());
            $this->assertSame('Sudeste', $aba->getCell('B7')->getValue());
            $this->assertStringContainsString('UF: SP', $aba->getCell('B3')->getValue());
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_download_devolve_o_token_para_fechar_o_carregando(): void
    {
        $this->actingAs($this->usuario());

        $response = $this->get(route('relatorios.cadastro.excel', ['download_token' => '1729450000000']));
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'relatorio_pronto');

        $this->assertNotNull($cookie);
        // Legível pelo JavaScript da tela: sem httpOnly e sem criptografia.
        $this->assertSame('1729450000000', $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());

        // Token que não é o gerado pela tela não vira cookie.
        $response = $this->get(route('relatorios.cadastro.pdf', ['download_token' => '<script>']));
        $this->assertNull(collect($response->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === 'relatorio_pronto'));
    }

    public function test_pdf_e_gerado(): void
    {
        $this->base();

        $response = $this->actingAs($this->usuario())->get(route('relatorios.cadastro.pdf', ['aplicado' => '1', 'tipo' => ['administradoras']]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
