<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O perfil Administrador administra perfis e permissões: cai direto na tela ao
 * entrar e tem acesso de edição à área que antes era exclusiva do Root.
 *
 * O teto continua sendo o Root — é isso que os testes do fim do arquivo travam.
 */
class AdministradorPerfisPermissoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(?string $role, string $email = 'user@teste.local'): User
    {
        $user = User::create([
            'name' => 'Usuário '.$role,
            'email' => $email,
            'password' => 'senha-secreta',
            'status' => true,
        ]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    public function test_login_do_administrador_cai_em_perfis_e_permissoes(): void
    {
        $this->usuario('Administrador');

        $response = $this->post(route('login.store'), [
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertAuthenticated();
    }

    /** A senha temporária do primeiro acesso continua vindo antes de tudo. */
    public function test_administrador_com_senha_temporaria_vai_trocar_a_senha_antes(): void
    {
        $user = $this->usuario('Administrador');
        $user->forceFill(['must_change_password' => true])->save();

        $response = $this->post(route('login.store'), [
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
        ]);

        $response->assertRedirect(route('password.change'));
    }

    /** O Root usa o sistema inteiro: segue caindo na lista de clientes. */
    public function test_login_do_root_continua_indo_para_clientes(): void
    {
        $this->usuario('Root');

        $response = $this->post(route('login.store'), [
            'email' => 'user@teste.local',
            'password' => 'senha-secreta',
        ]);

        $response->assertRedirect(route('clients.index', ['associado' => 1]));
    }

    public function test_administrador_abre_a_tela_de_perfis(): void
    {
        $this->actingAs($this->usuario('Administrador'))
            ->get(route('roles.index'))
            ->assertOk();
    }

    public function test_administrador_edita_as_permissoes_de_um_perfil(): void
    {
        $admin = $this->usuario('Administrador');
        $operador = \Spatie\Permission\Models\Role::where('name', 'Operador')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('roles.sync'), [
                'roles' => [$operador->id => ['clients.view']],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertSame(['clients.view'], $operador->fresh()->permissions->pluck('name')->all());
    }

    public function test_administrador_edita_as_permissoes_individuais_de_um_usuario(): void
    {
        $admin = $this->usuario('Administrador');
        $alvo = $this->usuario('Consulta', 'alvo@teste.local');

        $this->actingAs($admin)
            ->get(route('users.permissions.edit', $alvo))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('users.permissions.update', $alvo), [
                'permissions' => ['clients.view'],
            ])
            ->assertRedirect(route('users.permissions.edit', $alvo));

        $this->assertTrue($alvo->fresh()->can('clients.view'));
    }

    public function test_operador_continua_sem_acesso_a_area_de_permissoes(): void
    {
        $this->actingAs($this->usuario('Operador'))
            ->get(route('roles.index'))
            ->assertForbidden();
    }

    public function test_administrador_nao_promove_ninguem_a_root(): void
    {
        $admin = $this->usuario('Administrador');
        $alvo = $this->usuario('Consulta', 'alvo@teste.local');

        $this->actingAs($admin)
            ->put(route('users.role.update', $alvo), ['role' => 'Root'])
            ->assertForbidden();

        $this->assertFalse($alvo->fresh()->isRoot());
    }

    public function test_administrador_nao_mexe_num_usuario_root(): void
    {
        $admin = $this->usuario('Administrador');
        $root = $this->usuario('Root', 'root@teste.local');

        $this->actingAs($admin)
            ->get(route('users.permissions.edit', $root))
            ->assertForbidden();
    }

    /** Mesmo com o Root fora da tela, o perfil Root nunca perde permissões. */
    public function test_perfil_root_mantem_todas_as_permissoes_apos_sync_do_administrador(): void
    {
        $admin = $this->usuario('Administrador');
        $rootRole = \Spatie\Permission\Models\Role::where('name', 'Root')->firstOrFail();
        $total = \Spatie\Permission\Models\Permission::count();

        $this->actingAs($admin)->post(route('roles.sync'), ['roles' => [$rootRole->id => []]]);

        $this->assertSame($total, $rootRole->fresh()->permissions->count());
    }
}
