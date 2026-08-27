<?php

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\ClientContato;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\BancoEmMemoria;
use Tests\TestCase;

/**
 * Vínculos de comitê por contato.
 *
 * A carga do RM deduplicava só em memória, o que não segura duas execuções
 * simultâneas — a base chegou a ter 1.553 linhas para 1.050 vínculos reais.
 * A regra passou a ser do banco (migration 2026_08_10_000020) e a tela precisa
 * devolver mensagem de validação em vez de estourar o índice.
 */
class ClientComiteTest extends TestCase
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
            'name' => 'Usuário',
            'email' => 'comite@teste.local',
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        $user->assignRole('Administrador');

        return $user;
    }

    public function test_o_banco_impede_o_mesmo_contato_no_mesmo_comite_duas_vezes(): void
    {
        $nomes = array_column(Schema::getIndexes('client_comites'), 'name');

        $this->assertContains(
            'client_comites_client_contato_nome_unique',
            $nomes,
            'O índice único de client_comites sumiu — a carga volta a poder duplicar vínculos.'
        );
    }

    public function test_vinculo_repetido_vira_erro_de_validacao_e_nao_500(): void
    {
        $user = $this->usuario();
        $client = Client::factory()->create();
        $contato = ClientContato::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'nome' => 'Maria Souza',
        ]);

        $payload = [
            'contato_id' => $contato->id,
            'comite_nome' => 'Comitê Jurídico',
            'papel' => 'titular',
        ];

        $this->actingAs($user)->post(route('clients.comites.store', $client), $payload);
        $segundo = $this->actingAs($user)
            ->from(route('clients.show', ['client' => $client, 'tab' => 'cadastro']))
            ->post(route('clients.comites.store', $client), $payload);

        $segundo->assertSessionHasErrors('comite_nome');
        $this->assertSame(1, DB::table('client_comites')->count());
    }

    /** O mesmo comitê para contatos diferentes continua válido. */
    public function test_contatos_diferentes_podem_estar_no_mesmo_comite(): void
    {
        $user = $this->usuario();
        $client = Client::factory()->create();

        foreach (['Maria Souza', 'João Silva'] as $nome) {
            $contato = ClientContato::create([
                'client_id' => $client->id,
                'user_id' => $user->id,
                'nome' => $nome,
            ]);

            $this->actingAs($user)->post(route('clients.comites.store', $client), [
                'contato_id' => $contato->id,
                'comite_nome' => 'Comitê Jurídico',
                'papel' => 'titular',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, DB::table('client_comites')->count());
    }
}
