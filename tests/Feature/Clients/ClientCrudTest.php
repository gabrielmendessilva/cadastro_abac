<?php

namespace Tests\Feature\Clients;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CRUD de clientes ponta a ponta (rota -> controller -> request -> model -> view).
 *
 * Ao contrário dos testes de importação (AbacAdmin/Rm), que montam um schema
 * mínimo na mão, aqui rodamos as migrations de verdade via RefreshDatabase: o
 * teste de regressão estrutural no fim do arquivo só tem valor se as colunas
 * vierem das migrations, e não de um CREATE TABLE escrito dentro do teste.
 *
 * A migration 2026_05_04_000150_normalize_client_contatos_types é MySQL-only
 * (ALTER ... MODIFY / SHOW CREATE TABLE) e agora se auto-ignora fora do MySQL.
 */
class ClientCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerMysqlOnlyFunctionsOnSqlite();

        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * ClientController::index ordena os endereços com FIELD(), que é uma função
     * exclusiva do MySQL. Em produção (MySQL) funciona; em sqlite quebraria o
     * teste por um motivo que não é bug de produção. Registramos FIELD como UDF
     * no sqlite em vez de trocar o SQL do controller.
     */
    private function registerMysqlOnlyFunctionsOnSqlite(): void
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();

        if (! method_exists($pdo, 'sqliteCreateFunction')) {
            return;
        }

        // FIELD(needle, a, b, c) -> posição 1-based de needle na lista, 0 se ausente.
        $pdo->sqliteCreateFunction('FIELD', function ($needle, ...$haystack) {
            if ($needle === null) {
                return 0;
            }

            $position = array_search(
                (string) $needle,
                array_map(fn ($item) => (string) $item, $haystack),
                true
            );

            return $position === false ? 0 : $position + 1;
        }, -1);
    }

    private function userComRole(string $role = 'Administrador'): User
    {
        static $sequencia = 0;
        $sequencia++;

        $user = User::create([
            'name' => "Usuário {$sequencia}",
            'email' => "user{$sequencia}@teste.local",
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    /** Payload mínimo aceito pelo StoreClientRequest (só `name` é required). */
    private function payloadValido(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ACME ADMINISTRADORA DE CONSORCIOS LTDA',
            'fantasy_name' => 'ACME Consórcios',
            'document' => '12.345.678/0001-95',
            'email' => 'contato@acme.com.br',
            'phone' => '(11) 3000-4000',
            'status' => '1',
        ], $overrides);
    }

    // ---------------------------------------------------------------- INDEX

    public function test_index_renderiza_name_e_document_do_cliente(): void
    {
        $client = Client::factory()->create([
            'name' => 'ACME ADMINISTRADORA LTDA',
            'document' => '12.345.678/0001-95',
            'email' => 'contato@acme.com.br',
        ]);

        $response = $this->actingAs($this->userComRole())->get(route('clients.index'));

        $response->assertOk();
        // Se a view voltar a ler colunas PT-BR (nome/cpf_cnpj), estes valores
        // viram "-" e o teste falha — que foi exatamente o bug original.
        $response->assertSee('ACME ADMINISTRADORA LTDA');
        $response->assertSee('12.345.678/0001-95');
        $response->assertSee('contato@acme.com.br');
        $response->assertDontSee('Nenhum cliente encontrado.');

        $this->assertSame('ACME ADMINISTRADORA LTDA', $client->fresh()->name);
    }

    public function test_index_com_search_por_nome_responde_200_e_retorna_o_cliente(): void
    {
        Client::factory()->create(['name' => 'ACME ADMINISTRADORA LTDA']);
        Client::factory()->create(['name' => 'OUTRA EMPRESA SA']);

        // Um `where('nome', ...)` no controller derruba isso: em MySQL vira 500
        // (SQLSTATE 42S22 Unknown column 'nome'); em sqlite o identificador entre
        // aspas duplas degrada para string literal e a busca só devolve vazio.
        // O par assertOk + assertSee pega os dois casos.
        $response = $this->actingAs($this->userComRole())
            ->get(route('clients.index', ['search' => 'ACME']));

        $response->assertOk();
        $response->assertSee('ACME ADMINISTRADORA LTDA');
        $response->assertDontSee('OUTRA EMPRESA SA');
    }

    public function test_index_com_search_por_trecho_do_cnpj_encontra_o_cliente(): void
    {
        Client::factory()->create([
            'name' => 'ACME ADMINISTRADORA LTDA',
            'document' => '12.345.678/0001-95',
        ]);
        Client::factory()->create([
            'name' => 'OUTRA EMPRESA SA',
            'document' => '99.888.777/0001-11',
        ]);

        $response = $this->actingAs($this->userComRole())
            ->get(route('clients.index', ['search' => '345.678']));

        $response->assertOk();
        $response->assertSee('12.345.678/0001-95');
        $response->assertDontSee('99.888.777/0001-11');
    }

    // ---------------------------------------------------------------- STORE

    public function test_store_cria_o_cliente_nas_colunas_certas(): void
    {
        $user = $this->userComRole();

        $response = $this->actingAs($user)->post(route('clients.store'), $this->payloadValido());

        $client = Client::firstWhere('document', '12.345.678/0001-95');
        $this->assertNotNull($client, 'Cliente não foi persistido na coluna `document`.');

        $response->assertRedirect(route('clients.show', ['client' => $client, 'tab' => 'geral']));

        $this->assertDatabaseHas('clients', [
            'name' => 'ACME ADMINISTRADORA DE CONSORCIOS LTDA',
            'fantasy_name' => 'ACME Consórcios',
            'document' => '12.345.678/0001-95',
            'email' => 'contato@acme.com.br',
            'phone' => '(11) 3000-4000',
            'status' => true,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_store_rejeita_document_duplicado(): void
    {
        Client::factory()->create(['document' => '12.345.678/0001-95']);

        $response = $this->actingAs($this->userComRole())
            ->from(route('clients.create'))
            ->post(route('clients.store'), $this->payloadValido());

        $response->assertRedirect(route('clients.create'));
        $response->assertSessionHasErrors('document');

        $this->assertSame(1, Client::where('document', '12.345.678/0001-95')->count());
    }

    public function test_store_exige_name(): void
    {
        $response = $this->actingAs($this->userComRole())
            ->from(route('clients.create'))
            ->post(route('clients.store'), $this->payloadValido(['name' => '']));

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Client::count());
    }

    /**
     * `clients.document` é NOT NULL: sem esta regra o INSERT estoura com 500 em
     * vez de devolver erro de validação ao usuário.
     */
    public function test_store_exige_document(): void
    {
        $response = $this->actingAs($this->userComRole())
            ->from(route('clients.create'))
            ->post(route('clients.store'), $this->payloadValido(['document' => '']));

        $response->assertSessionHasErrors('document');
        $this->assertSame(0, Client::count());
    }

    /**
     * O banco guarda o documento mascarado. Sem normalizar antes de validar, o
     * mesmo CNPJ digitado sem pontuação escaparia da regra `unique` e criaria
     * um duplicado que quebra a busca por CNPJ e o dedup das importações.
     */
    public function test_store_normaliza_document_e_barra_duplicata_sem_mascara(): void
    {
        Client::factory()->create(['document' => '12.345.678/0001-95']);

        $response = $this->actingAs($this->userComRole())
            ->from(route('clients.create'))
            ->post(route('clients.store'), $this->payloadValido(['document' => '12345678000195']));

        $response->assertSessionHasErrors('document');
        $this->assertSame(1, Client::count());
    }

    public function test_store_grava_document_sempre_com_mascara(): void
    {
        $this->actingAs($this->userComRole())
            ->post(route('clients.store'), $this->payloadValido(['document' => '98765432000198']));

        $this->assertDatabaseHas('clients', ['document' => '98.765.432/0001-98']);
    }

    // --------------------------------------------------------------- UPDATE

    public function test_update_altera_campo_e_grava_client_audit_log(): void
    {
        $client = Client::factory()->create([
            'name' => 'NOME ANTIGO LTDA',
            'document' => '12.345.678/0001-95',
        ]);

        // A factory não passa pelo observer? Passa — logo já existe 1 log 'created'.
        $logsAntes = DB::table('client_audit_logs')->where('client_id', $client->id)->count();

        $user = $this->userComRole();

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'NOME NOVO LTDA',
            'document' => '12.345.678/0001-95', // mesmo doc: a regra unique deve ignorar o próprio registro
        ]);

        $response->assertRedirect(route('clients.show', ['client' => $client, 'tab' => 'geral']));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'NOME NOVO LTDA',
            'updated_by' => $user->id,
        ]);

        $this->assertGreaterThan(
            $logsAntes,
            DB::table('client_audit_logs')->where('client_id', $client->id)->count(),
            'O ClientObserver não gerou linha de auditoria para o update.'
        );

        $this->assertDatabaseHas('client_audit_logs', [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'campo' => 'name',
            'aba' => 'geral',
            'acao' => 'update',
            'valor_anterior' => 'NOME ANTIGO LTDA',
            'valor_novo' => 'NOME NOVO LTDA',
        ]);
    }

    // ----------------------------------------------------------------- SHOW

    public function test_show_renderiza_aba_geral_com_valores_reais(): void
    {
        $client = Client::factory()->create([
            'name' => 'ACME ADMINISTRADORA LTDA',
            'fantasy_name' => 'ACME Consórcios',
            'document' => '12.345.678/0001-95',
            'email' => 'contato@acme.com.br',
            'phone' => '(11) 3000-4000',
            'mobile' => '(11) 99999-8888',
        ]);

        $response = $this->actingAs($this->userComRole())
            ->get(route('clients.show', ['client' => $client, 'tab' => 'geral']));

        $response->assertOk();
        $response->assertSee('ACME ADMINISTRADORA LTDA');
        $response->assertSee('ACME Consórcios');
        $response->assertSee('12.345.678/0001-95');
        $response->assertSee('contato@acme.com.br');
        $response->assertSee('(11) 3000-4000');
        $response->assertSee('(11) 99999-8888');
    }

    // ---------------------------------------------------------- PERMISSÕES

    public function test_usuario_sem_permissao_de_criar_recebe_403(): void
    {
        // Consulta só tem clients.view / documents.view.
        $response = $this->actingAs($this->userComRole('Consulta'))
            ->post(route('clients.store'), $this->payloadValido());

        $response->assertForbidden();
        $this->assertSame(0, Client::count());
    }

    // ------------------------------------------- REGRESSÃO ESTRUTURAL

    /**
     * Trava a regressão de vez: toda chave validada pelos FormRequests precisa
     * existir como coluna real em `clients`. Se alguém renomear uma coluna (ou
     * validar um campo que não existe), o mass-assignment silenciosamente falha
     * ou explode em runtime — aqui falha no CI, apontando o nome exato.
     *
     */
    #[DataProvider('requestsDeCliente')]
    public function test_todas_as_chaves_validadas_existem_como_coluna_em_clients(string $requestClass): void
    {
        $colunas = array_column(Schema::getColumns('clients'), 'name');

        $this->assertNotEmpty($colunas, 'Não foi possível ler as colunas de `clients`.');

        $chaves = array_keys((new $requestClass)->rules());
        $semColuna = array_values(array_diff($chaves, $colunas));

        $this->assertSame(
            [],
            $semColuna,
            sprintf(
                "%s valida campo(s) que não existem em `clients`: %s.\n".
                "Ou a coluna foi renomeada, ou a regra está sobrando.",
                class_basename($requestClass),
                implode(', ', $semColuna)
            )
        );
    }

    /**
     * A checagem de existência acima não bastaria: `document` existia como coluna
     * mas era validado como `nullable` apesar de ser NOT NULL, e salvar sem CNPJ
     * devolvia 500 em vez de erro de validação. Aqui garantimos que toda coluna
     * NOT NULL sem default que o formulário aceita exige valor.
     */
    public function test_colunas_not_null_sem_default_sao_obrigatorias_no_store(): void
    {
        $regras = (new StoreClientRequest)->rules();
        $frouxas = [];

        foreach (Schema::getColumns('clients') as $coluna) {
            $nome = $coluna['name'];

            $obrigatoriaNoBanco = ! $coluna['nullable']
                && ($coluna['default'] ?? null) === null
                && ! ($coluna['auto_increment'] ?? false);

            if (! $obrigatoriaNoBanco || ! isset($regras[$nome])) {
                continue;
            }

            if (! in_array('required', (array) $regras[$nome], true)) {
                $frouxas[] = $nome;
            }
        }

        $this->assertSame(
            [],
            $frouxas,
            sprintf(
                "Coluna(s) NOT NULL sem default validada(s) como opcional(is): %s.\n".
                'Gravar sem esses campos estoura QueryException (HTTP 500) em vez de erro de validação.',
                implode(', ', $frouxas)
            )
        );
    }

    public static function requestsDeCliente(): array
    {
        return [
            'StoreClientRequest' => [StoreClientRequest::class],
            'UpdateClientRequest' => [UpdateClientRequest::class],
        ];
    }

    /**
     * Contrapartida: o núcleo em inglês tem que continuar existindo. Se alguém
     * reverter para PT-BR (nome/cpf_cnpj/email_admin/...), isso falha primeiro.
     */
    public function test_nucleo_de_clients_esta_em_ingles(): void
    {
        $colunas = array_column(Schema::getColumns('clients'), 'name');

        foreach (['name', 'fantasy_name', 'document', 'email', 'phone', 'mobile', 'notes', 'segmento', 'regional_id'] as $esperada) {
            $this->assertContains($esperada, $colunas, "Coluna `{$esperada}` sumiu de `clients`.");
        }

        foreach (['nome', 'nome_fantasia', 'cpf_cnpj', 'email_admin', 'telefone', 'celular_admin', 'obs', 'segmentos'] as $legada) {
            $this->assertNotContains($legada, $colunas, "Coluna legada `{$legada}` reapareceu em `clients`.");
        }
    }
}
