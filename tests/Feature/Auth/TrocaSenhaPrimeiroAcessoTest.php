<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Primeiro acesso: enquanto a senha temporária não for trocada, o usuário só
 * enxerga a tela de troca. Depois da troca a vida segue normal.
 */
class TrocaSenhaPrimeiroAcessoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(bool $deveTrocar = true): User
    {
        $user = User::create([
            'name' => 'Fulano',
            'email' => 'fulano@teste.local',
            'password' => 'senha-temporaria',
            'status' => true,
            'must_change_password' => $deveTrocar,
        ]);

        $user->assignRole('Consulta');

        return $user;
    }

    public function test_login_com_senha_temporaria_cai_direto_na_troca(): void
    {
        $this->usuario();

        $this->post(route('login.store'), [
            'email' => 'fulano@teste.local',
            'password' => 'senha-temporaria',
        ])->assertRedirect(route('password.change'));
    }

    public function test_tela_de_troca_abre_para_quem_esta_pendente(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('Crie sua senha')
            ->assertSee('fulano@teste.local');
    }

    public function test_qualquer_tela_do_sistema_devolve_para_a_troca(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)->get(route('clients.index'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.change'));
        $this->actingAs($user)->get(route('users.index'))->assertRedirect(route('password.change'));
    }

    public function test_troca_libera_o_sistema_e_grava_a_nova_senha(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'senha-temporaria',
                'password' => 'minha-senha-nova',
                'password_confirmation' => 'minha-senha-nova',
            ])
            ->assertRedirect(route('clients.index', ['associado' => 1]));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('minha-senha-nova', $user->password));

        $this->actingAs($user)->get(route('clients.index'))->assertOk();
    }

    public function test_senha_temporaria_errada_barra_a_troca(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'chutei-essa',
                'password' => 'minha-senha-nova',
                'password_confirmation' => 'minha-senha-nova',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    public function test_nova_senha_nao_pode_ser_a_temporaria(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'senha-temporaria',
                'password' => 'senha-temporaria',
                'password_confirmation' => 'senha-temporaria',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->must_change_password);
    }

    /** Quem já trocou não tem o que fazer nessa tela — nem pode trocar de novo por ali. */
    public function test_usuario_sem_pendencia_nao_entra_na_tela_de_troca(): void
    {
        $user = $this->usuario(deveTrocar: false);

        $this->actingAs($user)
            ->get(route('password.change'))
            ->assertRedirect(route('clients.index', ['associado' => 1]));

        $this->actingAs($user)
            ->post(route('password.change.store'), [
                'current_password' => 'senha-temporaria',
                'password' => 'outra-senha-qualquer',
                'password_confirmation' => 'outra-senha-qualquer',
            ])
            ->assertRedirect(route('clients.index', ['associado' => 1]));

        $this->assertTrue(Hash::check('senha-temporaria', $user->refresh()->password));
    }

    public function test_logout_continua_disponivel_com_a_senha_pendente(): void
    {
        $this->actingAs($this->usuario())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
