<?php

namespace App\Services\Associados;

use App\Models\Client;
use App\Services\Associados\Exceptions\AssociadosSyncException;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psr\Log\LoggerInterface;

/**
 * Sobrescreve clients.email das empresas associadas com o e-mail vindo do
 * WordPress dos associados (conexão config('associados.connection')).
 *
 * Diferente do associados:sync, que só preenche coluna vazia: aqui o e-mail do
 * portal é a fonte da verdade e VENCE o que está no GED. É o que o pedido exige
 * — atualizar o e-mail da empresa de acordo com o banco de associados.
 *
 * Fonte do e-mail: a conta-empresa no WP é o usuário que carrega a razão social
 * (a meta que o meta_map aponta para `name`); o e-mail da empresa é o
 * user_email dessa conta. Todo o resto do cadastro fica intocado — só a coluna
 * email muda.
 *
 * Roda com Client::withoutEvents para não inundar client_audit_logs; a trilha
 * de cada troca fica no canal de log 'associados' e na amostra do relatório.
 */
class AtualizacaoEmailService
{
    private const WHEREIN_BATCH = 500;

    private readonly string $sourceConnection;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?string $sourceConnection = null,
    ) {
        $this->sourceConnection = $sourceConnection
            ?? (string) config('associados.connection', 'pgsql-associado');
    }

    public function run(AtualizacaoEmailOptions $options): AtualizacaoEmailReport
    {
        $report = new AtualizacaoEmailReport;

        $this->logger->info('associados.emails.start', ['dry_run' => $options->dryRun]);

        $this->preflight();

        $razaoMeta = $this->metaKeyDaRazaoSocial();
        $cnpjLike = (string) config('associados.cnpj_meta_like', '%cnpj_associada%');

        // Contas-empresa: quem tem a razão social preenchida.
        $empresaUserIds = $this->source()->table('wp_usermeta')
            ->where('meta_key', $razaoMeta)
            ->whereNotNull('meta_value')
            ->where('meta_value', '<>', '')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $cnpjPorUser = $this->cnpjPorUsuario($empresaUserIds, $cnpjLike);
        $emailPorUser = $this->emailPorUsuario($empresaUserIds);

        // Um cliente por CNPJ: entre contas-empresa do mesmo CNPJ vence o menor
        // user_id (determinístico, coerente com o resto do módulo).
        sort($empresaUserIds);
        $escolhidoPorDigits = [];

        foreach ($empresaUserIds as $userId) {
            $report->empresasLidas++;

            $digits = Normalizer::digits((string) ($cnpjPorUser[$userId] ?? ''));

            if (! Normalizer::isValidDoc($digits)) {
                $report->semCnpj++;

                continue;
            }

            if (isset($escolhidoPorDigits[$digits])) {
                $report->cnpjDuplicado++;

                continue;
            }

            $escolhidoPorDigits[$digits] = $userId;
        }

        $clientesPorDigits = $this->clientesPorDigits(array_keys($escolhidoPorDigits));

        foreach ($escolhidoPorDigits as $digits => $userId) {
            $emailOrigem = trim((string) ($emailPorUser[$userId] ?? ''));

            if (filter_var($emailOrigem, FILTER_VALIDATE_EMAIL) === false) {
                $report->emailInvalido++;
                $this->logger->warning('associados.emails.origem_invalida', [
                    'cnpj' => $digits,
                    'user_id' => $userId,
                    'email' => $emailOrigem,
                ]);

                continue;
            }

            $cliente = $clientesPorDigits[$digits] ?? null;

            if ($cliente === null) {
                $report->semCliente++;

                continue;
            }

            $atual = trim((string) $cliente->email);

            // Comparação sem caixa: 'X@Y.com' e 'x@y.com' são o mesmo e-mail e não
            // valem uma escrita (nem uma linha de log).
            if (mb_strtolower($atual) === mb_strtolower($emailOrigem)) {
                $report->semMudanca++;

                continue;
            }

            if (! $options->dryRun) {
                Client::withoutEvents(function () use ($cliente, $emailOrigem): void {
                    DB::table('clients')->where('id', $cliente->id)->update(['email' => $emailOrigem]);
                });
            }

            $report->registraMudanca((string) $cliente->document, $atual === '' ? '(vazio)' : $atual, $emailOrigem);

            $this->logger->info('associados.emails.atualizado', [
                'cnpj' => $digits,
                'client_id' => (int) $cliente->id,
                'de' => $atual,
                'para' => $emailOrigem,
                'dry_run' => $options->dryRun,
            ]);
        }

        $this->logger->info('associados.emails.done', $report->toArray());

        return $report;
    }

    /**
     * A conta-empresa é identificada pela meta de razão social — a mesma que o
     * meta_map manda para a coluna `name`. Sem esse de-para não há como saber
     * qual usuário do CNPJ é a empresa (os outros são pessoas).
     */
    private function metaKeyDaRazaoSocial(): string
    {
        $metaMap = (array) config('associados.meta_map', []);
        $metaKey = array_search('name', $metaMap, true);

        if (! is_string($metaKey) || $metaKey === '') {
            throw new AssociadosSyncException(
                'Nenhuma meta_key de config(associados.meta_map) aponta para a coluna `name` (razão social). '
                .'É por ela que a conta-empresa do CNPJ é encontrada no WordPress — mapeie-a antes de rodar.'
            );
        }

        return $metaKey;
    }

    /**
     * @param list<int> $userIds
     * @return array<int,string> user_id => valor cru do CNPJ
     */
    private function cnpjPorUsuario(array $userIds, string $cnpjLike): array
    {
        $mapa = [];

        foreach (array_chunk($userIds, self::WHEREIN_BATCH) as $lote) {
            $rows = $this->source()->table('wp_usermeta')
                ->whereIn('user_id', $lote)
                ->where('meta_key', 'like', $cnpjLike)
                ->whereNotNull('meta_value')
                ->where('meta_value', '<>', '')
                ->get(['user_id', 'meta_value']);

            foreach ($rows as $row) {
                // Primeiro CNPJ vence: uma conta-empresa não deve ter dois, mas se
                // tiver, manter o primeiro é estável entre execuções.
                $mapa[(int) $row->user_id] ??= (string) $row->meta_value;
            }
        }

        return $mapa;
    }

    /**
     * @param list<int> $userIds
     * @return array<int,string> user_id => user_email
     */
    private function emailPorUsuario(array $userIds): array
    {
        $mapa = [];

        foreach (array_chunk($userIds, self::WHEREIN_BATCH) as $lote) {
            foreach ($this->source()->table('wp_users')->whereIn('ID', $lote)->get(['ID', 'user_email']) as $row) {
                $mapa[(int) $row->ID] = (string) $row->user_email;
            }
        }

        return $mapa;
    }

    /**
     * @param list<string> $digitosList
     * @return array<string,object{id:int,document:string,email:?string}>
     */
    private function clientesPorDigits(array $digitosList): array
    {
        $mapa = [];

        foreach (DB::table('clients')->orderBy('id')->cursor() as $row) {
            $digits = Normalizer::digits((string) $row->document);

            if ($digits === '' || isset($mapa[$digits])) {
                continue;
            }

            $mapa[$digits] = $row;
        }

        // Só o que interessa a esta execução.
        return array_intersect_key($mapa, array_flip($digitosList));
    }

    private function preflight(): void
    {
        $schema = Schema::connection($this->sourceConnection);

        try {
            foreach (['wp_users', 'wp_usermeta'] as $tabela) {
                if (! $schema->hasTable($tabela)) {
                    throw AssociadosSyncException::tabelaAusente($this->sourceConnection, $tabela);
                }
            }
        } catch (AssociadosSyncException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw AssociadosSyncException::conexaoIndisponivel($this->sourceConnection, $e->getMessage());
        }
    }

    private function source(): ConnectionInterface
    {
        return DB::connection($this->sourceConnection);
    }
}
