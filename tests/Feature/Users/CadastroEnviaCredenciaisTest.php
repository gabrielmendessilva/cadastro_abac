<?php

namespace Tests\Feature\Users;

use App\Mail\CredenciaisDeAcesso;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Cadastro de usuário: quem cria não define senha. O sistema gera uma senha
 * temporária, manda por e-mail com a URL de login, e marca a conta para troca
 * obrigatória no primeiro acesso.
 */
class CadastroEnviaCredenciaisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::create([
            'name' => 'Administrador',
            'email' => 'admin@teste.local',
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        $user->assignRole('Administrador');

        return $user;
    }

    public function test_cadastro_envia_email_com_url_usuario_e_senha_temporaria(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@teste.local',
            'role' => 'Consulta',
            'status' => 1,
        ])->assertRedirect(route('users.index'));

        $novo = User::where('email', 'fulano@teste.local')->firstOrFail();

        Mail::assertSent(CredenciaisDeAcesso::class, function (CredenciaisDeAcesso $mail) use ($novo) {
            return $mail->hasTo('fulano@teste.local')
                && $mail->user->is($novo)
                && $mail->loginUrl === route('login')
                // A senha do e-mail é a que ficou hasheada no banco.
                && Hash::check($mail->senhaTemporaria, $novo->password);
        });
    }

    public function test_usuario_novo_nasce_obrigado_a_trocar_a_senha(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@teste.local',
            'role' => 'Consulta',
            'status' => 1,
        ]);

        $this->assertTrue(User::where('email', 'fulano@teste.local')->firstOrFail()->must_change_password);
    }

    /** A senha temporária tem que servir para o login — é o único acesso que a pessoa tem. */
    public function test_senha_temporaria_do_email_autentica_e_cai_na_troca_de_senha(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Fulano de Tal',
            'email' => 'fulano@teste.local',
            'role' => 'Consulta',
            'status' => 1,
        ]);

        $senha = null;
        Mail::assertSent(CredenciaisDeAcesso::class, function (CredenciaisDeAcesso $mail) use (&$senha) {
            $senha = $mail->senhaTemporaria;

            return true;
        });

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'fulano@teste.local',
            'password' => $senha,
        ])->assertRedirect(route('password.change'));

        $this->assertAuthenticated();
    }

    public function test_formulario_de_cadastro_nao_tem_campo_de_senha(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('name="password"', escape: false)
            ->assertSee('senha temporária', escape: false);
    }

    public function test_cadastro_nao_pede_mais_senha(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'Fulano de Tal',
                'email' => 'fulano@teste.local',
                'role' => 'Consulta',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'fulano@teste.local']);
    }
}
