<?php

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\ClientEndereco;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\BancoEmMemoria;
use Tests\TestCase;

/**
 * Relatório de cadastro (Excel e PDF). O que importa é sair com o mesmo
 * recorte da lista: os relatórios usam o ClientesFiltro da tela.
 */
class ClientRelatorioTest extends TestCase
{
    use BancoEmMemoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(): User
    {
        $user = User::create([
            'name' => 'Relatorista',
            'email' => 'relatorio@teste.local',
            'password' => 'senha-secreta',
            'status' => true,
        ]);
        $user->assignRole('Administrador');

        return $user;
    }

    /** Lê a planilha baixada e devolve a coluna A (razão social) das linhas de dados. */
    private function razoesSociais(string $conteudo): array
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'rel').'.xlsx';
        file_put_contents($arquivo, $conteudo);

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();
            $nomes = [];
            for ($linha = 7; $linha < $aba->getHighestRow(); $linha++) {
                $nomes[] = $aba->getCell("A{$linha}")->getValue();
            }

            return $nomes;
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_excel_respeita_os_filtros_da_lista(): void
    {
        $paulista = Client::factory()->create(['name' => 'ADMINISTRADORA PAULISTA', 'associado_abac' => true]);
        ClientEndereco::create(['client_id' => $paulista->id, 'tipo' => 'principal', 'cep' => '01310-100', 'rua' => 'Av. Paulista', 'numero' => '1000', 'bairro' => 'Bela Vista', 'pais' => 'Brasil', 'cod_ibge' => '3550308', 'estado' => 'SP', 'municipio' => 'São Paulo']);
        Client::factory()->create(['name' => 'ADMINISTRADORA MINEIRA', 'associado_abac' => true]);
        Client::factory()->create(['name' => 'NÃO ASSOCIADA', 'associado_abac' => false]);
        Client::factory()->inactive()->create(['name' => 'INATIVA', 'associado_abac' => true]);

        // Sem parâmetro: padrão da tela (ativas + associadas).
        $response = $this->actingAs($this->usuario())->get(route('clients.relatorio.excel', ['sort' => 'name']));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertSame(['ADMINISTRADORA MINEIRA', 'ADMINISTRADORA PAULISTA'], $this->razoesSociais($response->streamedContent()));

        $response = $this->get(route('clients.relatorio.excel', ['state' => 'SP']));
        $this->assertSame(['ADMINISTRADORA PAULISTA'], $this->razoesSociais($response->streamedContent()));
    }

    public function test_pdf_e_gerado(): void
    {
        Client::factory()->create(['name' => 'ACME CONSORCIOS', 'associado_abac' => true]);

        $response = $this->actingAs($this->usuario())->get(route('clients.relatorio.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_relatorio_exige_login(): void
    {
        $this->get(route('clients.relatorio.excel'))->assertRedirect();
        $this->get(route('clients.relatorio.pdf'))->assertRedirect();
    }
}
