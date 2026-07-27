<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Depois do login o usuário cai direto na lista de clientes já filtrada em
 * Administradora = Associado (S) — a visão de trabalho do dia a dia.
 */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(?string $role = 'Consulta'): User
    {
        $user = User::create([
            'name' => 'Usuário',
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_login_leva_para_clientes_filtrado_por_associado(): void
    {
        $this->usuario();

        $response = $this->post(route('login.store'), [
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
        ]);

        $response->assertRedirect(route('clients.index', ['associado' => 1]));
        $this->assertAuthenticated();
    }

    /** Sem clients.view a lista devolveria 403 — esse usuário vai para o dashboard. */
    public function test_usuario_sem_permissao_de_ver_clientes_cai_no_dashboard(): void
    {
        $this->usuario(role: null);

        $response = $this->post(route('login.store'), [
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_usuario_ja_logado_que_abre_o_login_vai_para_a_mesma_tela(): void
    {
        $response = $this->actingAs($this->usuario())->get(route('login'));

        $response->assertRedirect(route('clients.index', ['associado' => 1]));
    }
}
