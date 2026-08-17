<?php

namespace Tests\Feature\Aniversariantes;

use App\Models\Client;
use App\Models\ClientContato;
use App\Models\ClientEndereco;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Tela e exportação dos aniversariantes do mês — a versão no banco do app da
 * consulta que era rodada direto no RM.
 *
 * A regra que mais importa é o "OR" da origem: contato sem data de nascimento
 * completa entra pelo aniversário dd/mm, que para a maioria dos contatos
 * importados é a única informação que existe.
 */
class AniversariantesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(string $role = 'Administrador'): User
    {
        static $sequencia = 0;
        $sequencia++;

        $user = User::create([
            'name' => "Usuário {$sequencia}",
            'email' => "aniver{$sequencia}@teste.local",
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /** `client_contatos.user_id` é NOT NULL nas migrations — todo contato tem autor. */
    private function contato(Client $client, array $attrs): ClientContato
    {
        return ClientContato::create($attrs + [
            'client_id' => $client->id,
            'user_id' => $this->autor()->id,
            'nome' => 'Contato',
        ]);
    }

    private function autor(): User
    {
        return $this->autor ??= $this->usuario();
    }

    private ?User $autor = null;

    public function test_lista_traz_quem_tem_aniversario_no_mes_por_qualquer_uma_das_duas_fontes(): void
    {
        $client = Client::factory()->create(['name' => 'ACME ADMINISTRADORA LTDA']);

        // Só o dd/mm (o caso mais comum na base importada do RM).
        $this->contato($client, ['nome' => 'So Aniversario', 'aniversario' => '16/09']);
        // Só a data completa.
        $this->contato($client, ['nome' => 'So Nascimento', 'dt_nascimento' => '1980-09-03']);
        // Outro mês: fica de fora.
        $this->contato($client, ['nome' => 'Fora Do Mes', 'aniversario' => '16/10', 'dt_nascimento' => '1980-10-16']);

        $response = $this->actingAs($this->usuario())->get(route('aniversariantes.index', ['mes' => 9]));

        $response->assertOk();
        $response->assertSee('So Aniversario');
        $response->assertSee('So Nascimento');
        $response->assertDontSee('Fora Do Mes');
        $response->assertSee('2 aniversariantes');
    }

    public function test_lista_ordena_por_dia_do_mes(): void
    {
        $client = Client::factory()->create(['name' => 'ACME']);
        $this->contato($client, ['nome' => 'Dia Vinte', 'aniversario' => '20/09']);
        $this->contato($client, ['nome' => 'Dia Tres', 'dt_nascimento' => '1980-09-03']);

        $html = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.index', ['mes' => 9]))
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Dia Vinte'),
            strpos($html, 'Dia Tres'),
            'A lista deveria vir ordenada pelo dia do mês.'
        );
    }

    public function test_mes_padrao_e_o_atual(): void
    {
        $client = Client::factory()->create(['name' => 'ACME']);
        $this->contato($client, ['nome' => 'Deste Mes', 'aniversario' => now()->format('d/m')]);

        $response = $this->actingAs($this->usuario())->get(route('aniversariantes.index'));

        $response->assertOk();
        $response->assertSee('Deste Mes');
    }

    public function test_filtra_por_administradora_associada(): void
    {
        $associada = Client::factory()->create(['name' => 'ASSOCIADA LTDA', 'associado_abac' => true]);
        $outra = Client::factory()->create(['name' => 'NAO ASSOCIADA SA', 'associado_abac' => false]);

        $this->contato($associada, ['nome' => 'Contato Associada', 'aniversario' => '05/09']);
        $this->contato($outra, ['nome' => 'Contato Outra', 'aniversario' => '06/09']);

        $response = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.index', ['mes' => 9, 'associado' => 1]));

        $response->assertSee('Contato Associada');
        $response->assertDontSee('Contato Outra');
    }

    /**
     * A lista é de contato de empresa ativa. Quem foi desativado no app — ou
     * desativado pela carga do RM, por estar lá com STATUS/OCORRENCIA fora de
     * 'OK' — não gera aniversariante.
     */
    public function test_contato_de_empresa_inativa_fica_de_fora(): void
    {
        $ativa = Client::factory()->create(['name' => 'ATIVA LTDA']);
        $inativa = Client::factory()->inactive()->create(['name' => 'INATIVA LTDA']);

        $this->contato($ativa, ['nome' => 'Contato Ativa', 'aniversario' => '05/09']);
        $this->contato($inativa, ['nome' => 'Contato Inativa', 'aniversario' => '06/09']);

        $response = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.index', ['mes' => 9]));

        $response->assertSee('Contato Ativa');
        $response->assertDontSee('Contato Inativa');
        $response->assertSee('1 aniversariante');
    }

    public function test_exportacao_nao_leva_contato_de_empresa_inativa(): void
    {
        $ativa = Client::factory()->create(['name' => 'ATIVA']);
        $inativa = Client::factory()->inactive()->create(['name' => 'INATIVA']);
        $this->contato($ativa, ['nome' => 'Vai Para O Excel', 'aniversario' => '05/09']);
        $this->contato($inativa, ['nome' => 'Nao Vai', 'aniversario' => '06/09']);

        $response = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.export', ['mes' => 9]));

        $arquivo = tempnam(sys_get_temp_dir(), 'aniver') . '.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();

            $this->assertSame(2, $aba->getHighestRow(), 'Deveria haver só o cabeçalho e uma linha.');
            $this->assertSame('Vai Para O Excel', $aba->getCell('L2')->getValue());
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_exportacao_gera_xlsx_com_os_dados_da_tela(): void
    {
        $client = Client::factory()->create([
            'name' => 'ACME ADMINISTRADORA LTDA',
            'fantasy_name' => 'ACME',
            'phone' => '(11) 3000-4000',
            'categoria' => 'CAT ESPECIAL',
            'associado_abac' => true,
        ]);

        ClientEndereco::create([
            'client_id' => $client->id, 'tipo' => 'principal',
            'cep' => '01310-100', 'rua' => 'Av. Paulista', 'numero' => '1000',
            'complemento' => 'cj 101', 'bairro' => 'Bela Vista', 'pais' => 'Brasil',
            'estado' => 'SP', 'cod_ibge' => '3550308', 'municipio' => 'São Paulo',
        ]);

        $this->contato($client, [
            'nome' => 'Maria Souza',
            'funcao' => 'Diretora',
            'departamento' => 'DIRETORIA',
            'email' => 'maria@acme.com.br',
            'celular' => '(11) 99999-8888',
            'aniversario' => '16/09',
            'dt_nascimento' => '1980-09-16',
        ]);

        $response = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.export', ['mes' => 9]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $arquivo = tempnam(sys_get_temp_dir(), 'aniver') . '.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();

            $this->assertSame('Empresa', $aba->getCell('A1')->getValue());
            $this->assertSame('ACME ADMINISTRADORA LTDA', $aba->getCell('A2')->getValue());
            $this->assertSame('Av. Paulista', $aba->getCell('C2')->getValue());
            $this->assertSame('São Paulo', $aba->getCell('G2')->getValue());
            $this->assertSame('maria@acme.com.br', $aba->getCell('K2')->getValue());
            $this->assertSame('Maria Souza', $aba->getCell('L2')->getValue());
            $this->assertSame('Sim', $aba->getCell('V2')->getValue());

            // "16/09" tem que continuar texto: como número, o Excel o transforma
            // em 16 de setembro do ano corrente e a coluna perde o sentido.
            $this->assertSame('16/09', $aba->getCell('T2')->getValue());
            $this->assertIsString($aba->getCell('T2')->getValue());
            // CEP e telefone também: como número perderiam o zero à esquerda.
            $this->assertSame('01310-100', $aba->getCell('I2')->getValue());

            // Data de nascimento vai como data de verdade, formatada em pt-BR.
            $this->assertSame('dd/mm/yyyy', $aba->getStyle('S2')->getNumberFormat()->getFormatCode());

            // Cabeçalho congelado e filtro pronto para uso.
            $this->assertSame('A2', $aba->getFreezePane());
            $this->assertNotNull($aba->getAutoFilter()->getRange());
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_exportacao_respeita_os_filtros_da_tela(): void
    {
        $associada = Client::factory()->create(['name' => 'ASSOCIADA', 'associado_abac' => true]);
        $outra = Client::factory()->create(['name' => 'NAO ASSOCIADA', 'associado_abac' => false]);
        $this->contato($associada, ['nome' => 'Vai Para O Excel', 'aniversario' => '05/09']);
        $this->contato($outra, ['nome' => 'Nao Vai', 'aniversario' => '06/09']);

        $response = $this->actingAs($this->usuario())
            ->get(route('aniversariantes.export', ['mes' => 9, 'associado' => 1]));

        $arquivo = tempnam(sys_get_temp_dir(), 'aniver') . '.xlsx';
        file_put_contents($arquivo, $response->streamedContent());

        try {
            $aba = IOFactory::load($arquivo)->getActiveSheet();

            $this->assertSame(2, $aba->getHighestRow(), 'Deveria haver só o cabeçalho e uma linha.');
            $this->assertSame('Vai Para O Excel', $aba->getCell('L2')->getValue());
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_usuario_sem_permissao_de_ver_clientes_recebe_403(): void
    {
        $user = User::create([
            'name' => 'Sem Permissao',
            'email' => 'sem@teste.local',
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        $this->actingAs($user)->get(route('aniversariantes.index'))->assertForbidden();
        $this->actingAs($user)->get(route('aniversariantes.export'))->assertForbidden();
    }
}
