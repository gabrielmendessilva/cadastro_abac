<?php

namespace App\Console\Commands;

use App\Mail\CredenciaisDeAcesso;
use App\Models\User;
use App\Support\SenhaTemporaria;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * Reenvia os dados de acesso para quem já estava cadastrado antes do fluxo de
 * senha temporária existir.
 *
 * Cada usuário atingido tem a senha ATUAL substituída por uma temporária e passa
 * a cair na tela de troca no próximo login — ou seja, a senha antiga deixa de
 * funcionar. Por isso o comando pede confirmação e tem --dry-run.
 *
 * Usuários Root ficam sempre de fora: são as contas que destrancam o sistema, e
 * derrubar a senha delas junto com as outras arriscaria perder o acesso caso o
 * e-mail não chegue.
 *
 * Se o envio falhar, a senha antiga daquele usuário é restaurada — ninguém fica
 * com uma senha temporária que não chegou a ser entregue.
 */
class ReenviarAcesso extends Command
{
    protected $signature = 'usuarios:reenviar-acesso
        {--dry-run : Não gera senha nem envia; só lista quem receberia}
        {--email=* : Limita o envio aos e-mails informados}
        {--apenas-ativos : Deixa de fora os usuários inativos}
        {--url= : Base das URLs do e-mail, ex: https://ged.abac-admin.cloud (default: APP_URL)}
        {--intervalo=0 : Segundos de pausa entre um envio e outro}
        {--force : Não pede confirmação}';

    protected $description = 'Gera uma nova senha temporária e reenvia os dados de acesso por e-mail (exceto usuários Root)';

    public function handle(): int
    {
        if ($base = $this->option('url')) {
            $base = rtrim($base, '/');
            URL::forceRootUrl($base);

            // forceRootUrl só troca o host: sem isto o esquema continua vindo do
            // ambiente (http no CLI) e o link sairia http:// mesmo com --url https.
            if (str_starts_with($base, 'https://')) {
                URL::forceScheme('https');
            }
        }

        $loginUrl = route('login');

        if (! $this->urlEhPublica($loginUrl)) {
            $this->error("O link que iria no e-mail é {$loginUrl} — ninguém consegue abrir isso fora do servidor.");
            $this->line('Corrija o APP_URL no .env ou rode com --url=https://ged.abac-admin.cloud');

            return self::FAILURE;
        }

        $usuarios = $this->destinatarios();

        if ($usuarios->isEmpty()) {
            $this->warn('Nenhum usuário elegível com esses filtros. Nada a fazer.');

            return self::SUCCESS;
        }

        $this->info("Link de acesso que vai no e-mail: {$loginUrl}");
        $this->newLine();

        $this->table(
            ['Nome', 'E-mail', 'Perfil', 'Ativo'],
            $usuarios->map(fn (User $u) => [
                $u->name,
                $u->email,
                $u->getRoleNames()->first() ?? '—',
                $u->status ? 'sim' : 'não',
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->warn("DRY-RUN: {$usuarios->count()} usuário(s) receberiam o e-mail. Nenhuma senha foi trocada.");

            return self::SUCCESS;
        }

        $this->warn("A senha atual desses {$usuarios->count()} usuário(s) vai parar de funcionar agora mesmo.");

        if (! $this->option('force') && ! $this->confirm('Confirma o reenvio?', false)) {
            $this->line('Cancelado.');

            return self::SUCCESS;
        }

        $intervalo = max(0, (int) $this->option('intervalo'));
        $enviados = 0;
        $falhas = [];

        $this->newLine();

        foreach ($usuarios as $user) {
            if ($this->reenviar($user, $loginUrl, $falhas)) {
                $enviados++;
            }

            if ($intervalo > 0 && $user !== $usuarios->last()) {
                sleep($intervalo);
            }
        }

        $this->newLine();
        $this->table(['Métrica', 'Qtd'], [
            ['e-mails enviados', $enviados],
            ['falhas (senha antiga mantida)', count($falhas)],
        ]);

        if ($falhas !== []) {
            $this->newLine();
            $this->error('Falharam (nada foi alterado nesses usuários):');

            foreach ($falhas as $falha) {
                $this->line("  - {$falha['email']}: {$falha['erro']}");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Troca a senha e envia o e-mail. Se o envio estourar, devolve o usuário ao
     * estado anterior — inclusive a flag de troca obrigatória.
     */
    private function reenviar(User $user, string $loginUrl, array &$falhas): bool
    {
        $hashAnterior = $user->getRawOriginal('password');
        $flagAnterior = $user->must_change_password;

        $senha = SenhaTemporaria::gerar();

        // O cast 'hashed' do model faz o Hash::make na atribuição; na volta, o
        // valor já é um hash e passa direto.
        $user->forceFill([
            'password' => $senha,
            'must_change_password' => true,
        ])->save();

        try {
            Mail::to($user->email)->send(new CredenciaisDeAcesso($user, $senha, $loginUrl));
        } catch (\Throwable $e) {
            $user->forceFill([
                'password' => $hashAnterior,
                'must_change_password' => $flagAnterior,
            ])->save();

            report($e);
            $falhas[] = ['email' => $user->email, 'erro' => $e->getMessage()];

            $this->components->twoColumnDetail($user->email, '<fg=red>falhou</>');

            return false;
        }

        $this->components->twoColumnDetail($user->email, '<fg=green>enviado</>');

        return true;
    }

    /** @return Collection<int, User> */
    private function destinatarios(): Collection
    {
        $emails = array_filter((array) $this->option('email'));

        return User::query()
            ->with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Root'))
            ->when($emails !== [], fn ($q) => $q->whereIn('email', $emails))
            ->when($this->option('apenas-ativos'), fn ($q) => $q->where('status', true))
            ->orderBy('name')
            ->get()
            // Rede de proteção: o filtro acima é SQL, este usa a mesma regra que
            // o resto do sistema (Gate::before, EnsureUserIsRoot).
            ->reject(fn (User $u) => $u->isRoot())
            ->values();
    }

    /** O e-mail sai para fora do servidor; um link para localhost não serve para ninguém. */
    private function urlEhPublica(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';

        return ! in_array($host, ['localhost', '127.0.0.1', '::1', ''], true);
    }
}
