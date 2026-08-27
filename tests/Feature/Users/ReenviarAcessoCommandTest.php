<?php

namespace Tests\Feature\Users;

use App\Mail\CredenciaisDeAcesso;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\BancoEmMemoria;
use Tests\TestCase;

/**
 * usuarios:reenviar-acesso — reemite a senha temporária de quem já estava
 * cadastrado. Root nunca entra; falha de envio não deixa ninguém trancado.
 */
class ReenviarAcessoCommandTest extends TestCase
{
    use BancoEmMemoria;

    private const URL = 'https://ged.abac-admin.cloud';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function usuario(string $email, string $role, bool $ativo = true): User
    {
        $user = User::create([
            'name' => 'Usuário '.$email,
            'email' => $email,
            'password' => 'senha-antiga',
            'status' => $ativo,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function test_reenvia_para_todos_menos_root(): void
    {
        Mail::fake();

        $root = $this->usuario('root@teste.local', 'Root');
        $admin = $this->usuario('admin@teste.local', 'Administrador');
        $consulta = $this->usuario('consulta@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', ['--force' => true, '--url' => self::URL])
            ->assertSuccessful();

        // Root intocado: mesma senha, sem troca obrigatória, sem e-mail.
        $root->refresh();
        $this->assertTrue(Hash::check('senha-antiga', $root->password));
        $this->assertFalse($root->must_change_password);
        Mail::assertNotSent(CredenciaisDeAcesso::class, fn ($m) => $m->hasTo('root@teste.local'));

        foreach ([$admin, $consulta] as $user) {
            $user->refresh();
            $this->assertFalse(Hash::check('senha-antiga', $user->password), "{$user->email} ficou com a senha antiga.");
            $this->assertTrue($user->must_change_password, "{$user->email} não foi marcado para trocar a senha.");
        }

        Mail::assertSent(CredenciaisDeAcesso::class, 2);
    }

    public function test_a_senha_do_email_e_a_que_vale_para_entrar(): void
    {
        Mail::fake();

        $user = $this->usuario('fulano@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', ['--force' => true, '--url' => self::URL]);

        Mail::assertSent(CredenciaisDeAcesso::class, function (CredenciaisDeAcesso $mail) use ($user) {
            return $mail->hasTo('fulano@teste.local')
                && $mail->loginUrl === self::URL.'/login'
                && Hash::check($mail->senhaTemporaria, $user->refresh()->password);
        });
    }

    public function test_dry_run_nao_altera_nem_envia(): void
    {
        Mail::fake();

        $user = $this->usuario('fulano@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', ['--dry-run' => true, '--url' => self::URL])
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue(Hash::check('senha-antiga', $user->password));
        $this->assertFalse($user->must_change_password);
        Mail::assertNothingSent();
    }

    public function test_opcao_email_limita_o_alvo(): void
    {
        Mail::fake();

        $alvo = $this->usuario('alvo@teste.local', 'Consulta');
        $outro = $this->usuario('outro@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', [
            '--force' => true,
            '--url' => self::URL,
            '--email' => ['alvo@teste.local'],
        ])->assertSuccessful();

        $this->assertFalse(Hash::check('senha-antiga', $alvo->refresh()->password));
        $this->assertTrue(Hash::check('senha-antiga', $outro->refresh()->password));
        Mail::assertSent(CredenciaisDeAcesso::class, 1);
    }

    public function test_apenas_ativos_deixa_inativo_de_fora(): void
    {
        Mail::fake();

        $inativo = $this->usuario('inativo@teste.local', 'Consulta', ativo: false);

        $this->artisan('usuarios:reenviar-acesso', [
            '--force' => true,
            '--url' => self::URL,
            '--apenas-ativos' => true,
        ])->assertSuccessful();

        $this->assertTrue(Hash::check('senha-antiga', $inativo->refresh()->password));
        Mail::assertNothingSent();
    }

    /** Sem isso o usuário ficaria com uma senha temporária que ninguém recebeu. */
    public function test_falha_no_envio_devolve_a_senha_antiga(): void
    {
        $user = $this->usuario('fulano@teste.local', 'Consulta');

        Event::listen(MessageSending::class, function () {
            throw new \RuntimeException('SMTP fora do ar');
        });

        $this->artisan('usuarios:reenviar-acesso', ['--force' => true, '--url' => self::URL])
            ->assertFailed();

        $user->refresh();
        $this->assertTrue(Hash::check('senha-antiga', $user->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_recusa_rodar_com_link_para_localhost(): void
    {
        Mail::fake();

        $user = $this->usuario('fulano@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', ['--force' => true, '--url' => 'http://localhost'])
            ->assertFailed();

        $this->assertTrue(Hash::check('senha-antiga', $user->refresh()->password));
        Mail::assertNothingSent();
    }

    public function test_confirmacao_negada_nao_faz_nada(): void
    {
        Mail::fake();

        $user = $this->usuario('fulano@teste.local', 'Consulta');

        $this->artisan('usuarios:reenviar-acesso', ['--url' => self::URL])
            ->expectsConfirmation('Confirma o reenvio?', 'no')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('senha-antiga', $user->refresh()->password));
        Mail::assertNothingSent();
    }
}
