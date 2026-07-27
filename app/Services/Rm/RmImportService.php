<?php

namespace App\Services\Rm;

use App\Models\CentroCusto;
use App\Models\Client;
use App\Models\ClientContato;
use App\Models\ClientEndereco;
use App\Models\ClientRedeSocial;
use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Importação de clientes/fornecedores do TOTVS RM para o banco do app.
 *
 * Regras de negócio:
 * - Dedup de cliente por CPF/CNPJ (dígitos normalizados dos dois lados). Quem já
 *   existe é PULADO — nada é alterado em clients.
 * - Contatos entram para clientes novos E existentes: o e-mail só é cadastrado se
 *   ainda não existir para aquele cliente (em client_contatos ou nos e-mails do
 *   próprio clients). Contato sem e-mail deduplica por nome; sem ambos, é pulado.
 * - Centro de custo (GCCUSTO via FCFODEF) vira linha em centros_custo vinculada
 *   por client_id — tabela satélite, sem nenhuma coluna de referência ao RM.
 *   Coligada/código do RM são usados apenas em memória para resolver o join.
 * - `associado_abac` NUNCA é escrito por esta carga: quem é associado vem do
 *   legado (clients:backfill-legado) e do WordPress (associados:sync). O RM tem
 *   cli/for que não são associados da ABAC e sobrescrever isso apagaria a base.
 * - Campos livres da aba "Opcionais" do RM (CAMPOALFAOP1..3 / DATAOP1..3) são a
 *   única fonte de filiação ABAC/SINAC, data de abertura e site. Diferente do
 *   resto do cadastro, eles TAMBÉM entram em clientes já existentes (backfill),
 *   mas só onde a coluna de destino está vazia — o que foi digitado no app nunca
 *   é sobrescrito pelo RM.
 * - Idempotente: re-executar não duplica nada.
 */
class RmImportService
{
    /** @var array<string,int> dígitos do documento => clients.id */
    private array $byDigits = [];

    /** @var array<int,list<string>> clients.id => e-mails do próprio cadastro (email, email_2..email_7) */
    private array $emailSeed = [];

    /** Colunas de e-mail do cadastro do cliente, na ordem de preenchimento. */
    private const CLIENT_EMAIL_COLUMNS = ['email', 'email_2', 'email_3', 'email_4', 'email_5', 'email_6', 'email_7'];

    /** Colunas de clients alimentadas pelos campos opcionais da FCFO. */
    private const CLIENT_OPCIONAIS_COLUMNS = [
        'num_filiacao_abac', 'dt_filiacao_abac',
        'num_filiacao_sinac', 'dt_filiacao_sinac',
        'dt_abertura_empresa',
    ];

    /** @var array<int|string,array{emails:array<string,true>,names:array<string,true>}> */
    private array $contactKeys = [];

    /** @var array<string,true> documentos já vistos nesta execução (detecção de duplicado no RM) */
    private array $rmSeenDigits = [];

    /** @var array<string,array<string,mixed>> "coligada|codigo" (só em memória) => atributos do centro de custo */
    private array $ccDetails = [];

    /** @var array<string,true> "clientId|codigo" => centro de custo já existente/criado */
    private array $ccExisting = [];

    /** @var array<int,array<string,mixed>> clients.id => valor atual das colunas de CLIENT_OPCIONAIS_COLUMNS */
    private array $opcionaisAtuais = [];

    /** @var array<int,true> clients.id que já têm rede social do tipo 'site' */
    private array $siteExistente = [];

    private int $fakeId = 0;

    public function __construct(
        private readonly RmReaderInterface $reader,
        private readonly LoggerInterface $logger,
    ) {}

    public function run(RmImportOptions $options): RmImportReport
    {
        $report = new RmImportReport($options->maxWarningSamples);
        $this->resetState();

        $this->logger->info('rm.import.start', [
            'dry_run' => $options->dryRun,
            'limit' => $options->limit,
            'coligada' => $options->coligada,
            'chunk' => $options->chunkSize,
            'backfill' => $options->backfill,
        ]);

        $this->reader->preflight();

        $this->loadRmCentrosCusto($report);
        $this->loadClientMaps($report);

        $complColumns = $options->includeContatoCompl
            ? $this->reader->contatoComplCustomColumns()
            : [];

        Client::withoutEvents(function () use ($options, $report, $complColumns): void {
            $this->reader->eachFcfoChunk(
                $options->chunkSize,
                $options->coligada,
                $options->limit,
                function (array $rows) use ($options, $report, $complColumns): void {
                    $keys = [];
                    foreach ($rows as $row) {
                        $keys[(int) $row['CODCOLIGADA']][] = trim((string) $row['CODCFO']);
                    }

                    $contatosMap = $this->reader->contatosForKeys($keys);
                    $defsMap = $this->reader->defaultsForKeys($keys);
                    $complMap = $complColumns !== []
                        ? $this->reader->contatosComplForKeys($keys, $complColumns)
                        : [];

                    foreach ($rows as $fcfo) {
                        $key = ((int) $fcfo['CODCOLIGADA']) . '|' . trim((string) $fcfo['CODCFO']);

                        try {
                            $this->processFcfoRow(
                                $fcfo,
                                $contatosMap[$key] ?? [],
                                $defsMap[$key] ?? [],
                                $complMap,
                                $options,
                                $report,
                            );
                        } catch (Throwable $e) {
                            $report->erros++;
                            $this->warn($report, 'Falha ao processar registro FCFO — linha pulada', [
                                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                                'codcfo' => $fcfo['CODCFO'] ?? null,
                                'erro' => $e->getMessage(),
                            ]);
                        }
                    }

                    if ($options->onChunk !== null) {
                        ($options->onChunk)(count($rows));
                    }
                }
            );
        });

        $this->logger->info('rm.import.done', $report->toArray());

        return $report;
    }

    private function resetState(): void
    {
        $this->byDigits = [];
        $this->emailSeed = [];
        $this->contactKeys = [];
        $this->rmSeenDigits = [];
        $this->ccDetails = [];
        $this->ccExisting = [];
        $this->opcionaisAtuais = [];
        $this->siteExistente = [];
        $this->fakeId = 0;
    }

    /**
     * @param array<string,mixed> $fcfo
     * @param list<array<string,mixed>> $contatos
     * @param list<array<string,mixed>> $defRows
     * @param array<string,array<string,mixed>> $complMap
     */
    private function processFcfoRow(
        array $fcfo,
        array $contatos,
        array $defRows,
        array $complMap,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        $report->fcfoLidos++;

        $digits = Normalizer::digits((string) ($fcfo['CGCCFO'] ?? ''));

        if (! Normalizer::isValidDoc($digits)) {
            $report->clientsPuladosInvalidos++;
            $this->warn($report, 'CGCCFO vazio ou inválido — registro pulado (contatos inclusive)', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
                'cgccfo' => $fcfo['CGCCFO'] ?? null,
            ]);

            return;
        }

        $clientId = $this->byDigits[$digits] ?? null;

        if ($clientId !== null) {
            if (isset($this->rmSeenDigits[$digits])) {
                $report->duplicadosNoRm++;
                $this->warn($report, 'Documento duplicado dentro do RM — contatos vão para o cliente já importado', [
                    'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                    'codcfo' => $fcfo['CODCFO'] ?? null,
                    'client_id' => $clientId,
                ]);
            } else {
                $report->clientsPuladosExistentes++;
            }

            // O cadastro do cliente existente não é reescrito: o centro de custo e
            // o site são linhas novas em tabelas satélite, e os campos opcionais
            // só entram nas colunas que ainda estão vazias.
            if ($options->backfill) {
                $cc = $this->resolveCentroCusto($fcfo, $defRows, $report);

                if ($cc !== null && $this->attachCentroCusto($clientId, $cc, $options)) {
                    $report->backfillCentroCusto++;
                }

                $this->backfillOpcionais($clientId, $fcfo, $options, $report);

                $site = $this->resolveSite($fcfo, $report);

                if ($site !== null && $this->attachSite($clientId, $site, $options)) {
                    $report->redesSociaisCriadas++;
                }
            }
        } else {
            $clientId = $this->createClient($fcfo, $digits, $defRows, $options, $report);
        }

        $this->rmSeenDigits[$digits] = true;

        if ($contatos !== []) {
            $this->processContatos($clientId, $contatos, $complMap, $options, $report);
        }
    }

    /**
     * @param array<string,mixed> $fcfo
     * @param list<array<string,mixed>> $defRows
     */
    private function createClient(
        array $fcfo,
        string $digits,
        array $defRows,
        RmImportOptions $options,
        RmImportReport $report,
    ): int {
        $cc = $this->resolveCentroCusto($fcfo, $defRows, $report);
        $attrs = $this->buildClientAttributes($fcfo, $digits, $report);
        $enderecos = $this->buildEnderecos($fcfo);
        $site = $this->resolveSite($fcfo, $report);

        if ($options->dryRun) {
            $clientId = --$this->fakeId;
        } else {
            $clientId = DB::transaction(function () use ($attrs, $enderecos, $cc, $site): int {
                $client = Client::create($attrs);

                foreach ($enderecos as $endereco) {
                    ClientEndereco::create($endereco + ['client_id' => $client->id]);
                }

                if ($cc !== null) {
                    CentroCusto::create($cc + ['client_id' => $client->id]);
                }

                if ($site !== null) {
                    ClientRedeSocial::create($site + ['client_id' => $client->id]);
                }

                return (int) $client->id;
            });
        }

        $report->clientsCriados++;
        $report->enderecosCriados += count($enderecos);

        if ($cc !== null) {
            $report->centrosCustoCriados++;
            $this->ccExisting[$clientId . '|' . $cc['codigo']] = true;
        }

        if ($site !== null) {
            $report->redesSociaisCriadas++;
            $this->siteExistente[$clientId] = true;
        }

        $this->byDigits[$digits] = $clientId;

        // Duplicado do mesmo documento mais adiante no RM cai no caminho de
        // backfill: semeia o que já foi gravado para ele só completar buracos.
        $this->opcionaisAtuais[$clientId] = array_intersect_key(
            $attrs,
            array_flip(self::CLIENT_OPCIONAIS_COLUMNS),
        );

        // Semeia o dedup de contatos com os e-mails do próprio cliente recém-criado.
        $emails = [];
        foreach (self::CLIENT_EMAIL_COLUMNS as $col) {
            if (! empty($attrs[$col])) {
                $emails[$attrs[$col]] = true;
            }
        }
        $this->contactKeys[$clientId] = ['emails' => $emails, 'names' => []];

        return $clientId;
    }

    /**
     * Cria a linha de centros_custo para o cliente se o par (client_id, codigo)
     * ainda não existir. Retorna true quando criou (ou criaria, no dry-run).
     *
     * @param array<string,mixed> $cc
     */
    private function attachCentroCusto(int $clientId, array $cc, RmImportOptions $options): bool
    {
        $pair = $clientId . '|' . $cc['codigo'];

        if (isset($this->ccExisting[$pair])) {
            return false;
        }

        if (! $options->dryRun) {
            CentroCusto::create($cc + ['client_id' => $clientId]);
        }

        $this->ccExisting[$pair] = true;

        return true;
    }

    /**
     * @param array<string,mixed> $fcfo
     * @return array<string,mixed>
     */
    private function buildClientAttributes(array $fcfo, string $digits, RmImportReport $report): array
    {
        $isPf = strlen($digits) === 11;

        $nome = Normalizer::trimOrNull((string) ($fcfo['NOME'] ?? ''));
        $fantasia = Normalizer::trimOrNull((string) ($fcfo['NOMEFANTASIA'] ?? ''));

        if ($nome === null && $fantasia === null) {
            $nome = trim((string) ($fcfo['CODCFO'] ?? ''));
            $this->warn($report, 'FCFO sem NOME e sem NOMEFANTASIA — usando CODCFO como nome', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
            ]);
        }

        // Sanity-check do PESSOAFISOUJUR (a semântica varia por versão do RM; o tamanho do doc decide).
        $pfj = strtoupper(trim((string) ($fcfo['PESSOAFISOUJUR'] ?? '')));
        if (($pfj === 'F' && ! $isPf) || ($pfj === 'J' && $isPf)) {
            $this->warn($report, 'PESSOAFISOUJUR diverge do tamanho do documento — prevaleceu o documento', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
                'pessoafisoujur' => $pfj,
                'digitos' => strlen($digits),
            ]);
        }

        // União de todos os e-mails do RM, na ordem, sem repetição.
        $emails = Normalizer::splitEmails((string) ($fcfo['EMAIL'] ?? ''));
        foreach (['EMAILFISCAL', 'EMAILPGTO', 'EMAILENTREGA'] as $col) {
            foreach (Normalizer::splitEmails((string) ($fcfo[$col] ?? '')) as $email) {
                if (! in_array($email, $emails, true)) {
                    $emails[] = $email;
                }
            }
        }

        if (count($emails) > 7) {
            $report->emailsExcedentes += count($emails) - 7;
            $this->warn($report, 'Mais e-mails no RM do que colunas disponíveis (email, email_2..email_7)', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
                'excedentes' => array_slice($emails, 7),
            ]);
        }

        $emailsBoletos = Normalizer::splitEmails((string) ($fcfo['EMAILPGTO'] ?? ''));

        $ativo = $fcfo['ATIVO'] ?? null;

        // Núcleo da tabela em inglês (name/fantasy_name/document/email/phone/mobile/notes);
        // o resto são as extensões legadas em PT-BR. Larguras conforme o schema real.
        $attrs = [
            'name' => Normalizer::limit($nome, 255),
            'fantasy_name' => Normalizer::limit($fantasia ?? $nome, 255),
            'document' => Normalizer::limit(Normalizer::formatCpfCnpj($digits), 20),
            'inscri_estadual' => Normalizer::limit((string) ($fcfo['INSCRESTADUAL'] ?? ''), 50),
            'inscri_municipal' => Normalizer::limit((string) ($fcfo['INSCRMUNICIPAL'] ?? ''), 50),
            'tipo_cliente' => $this->mapPagRec($fcfo['PAGREC'] ?? null),
            'phone' => Normalizer::limit((string) ($fcfo['TELEFONE'] ?? ''), 20),
            'mobile' => Normalizer::limit((string) ($fcfo['TELEX'] ?? ''), 20),
            'contato_name_admin' => Normalizer::limit((string) ($fcfo['CONTATO'] ?? ''), 255),
            'email' => $emails[0] ?? null,
            'email_2' => $emails[1] ?? null,
            'email_3' => $emails[2] ?? null,
            'email_4' => $emails[3] ?? null,
            'email_5' => $emails[4] ?? null,
            'email_6' => $emails[5] ?? null,
            'email_7' => $emails[6] ?? null,
            'emails_boletos' => $emailsBoletos !== [] ? implode('; ', $emailsBoletos) : null,
            'area_atuacao' => Normalizer::trimOrNull((string) ($fcfo['RAMOATIV'] ?? '')),
            'notes' => Normalizer::trimOrNull((string) ($fcfo['CAMPOLIVRE'] ?? '')),
            'obs_cadastro' => sprintf(
                'Importado do TOTVS RM em %s — coligada %s, código %s.',
                now()->format('d/m/Y'),
                $fcfo['CODCOLIGADA'] ?? '?',
                trim((string) ($fcfo['CODCFO'] ?? '?')),
            ),
        ] + $this->buildOpcionais($fcfo);

        // clients.status é tinyint(1) NOT NULL default 1: grava booleano de verdade e,
        // quando o RM não informa ATIVO, deixa o default do banco valer.
        if ($ativo !== null) {
            $attrs['status'] = ((int) $ativo) === 1;
        }

        // O RM não tem regional: regional_id fica nulo, sem mapeamento inventado.

        if ($isPf) {
            $attrs['cpf'] = Normalizer::limit(Normalizer::formatCpfCnpj($digits), 20);
            $attrs['rg'] = Normalizer::limit((string) ($fcfo['CIDENTIDADE'] ?? ''), 30);
            $attrs['dt_nascimento'] = Normalizer::toDateOrNull($fcfo['DTNASCIMENTO'] ?? null);
        }

        return $attrs;
    }

    /**
     * Campos livres da aba "Opcionais" do RM, como a ABAC os usa:
     * CAMPOALFAOP2/DATAOP2 = filiação ABAC, CAMPOALFAOP3/DATAOP3 = filiação SINAC
     * e DATAOP1 = data de abertura da empresa.
     *
     * @param array<string,mixed> $fcfo
     * @return array<string,string|null> colunas de CLIENT_OPCIONAIS_COLUMNS
     */
    private function buildOpcionais(array $fcfo): array
    {
        return [
            'num_filiacao_abac' => Normalizer::limit((string) ($fcfo['CAMPOALFAOP2'] ?? ''), 50),
            'dt_filiacao_abac' => Normalizer::toDateOrNull($fcfo['DATAOP2'] ?? null),
            'num_filiacao_sinac' => Normalizer::limit((string) ($fcfo['CAMPOALFAOP3'] ?? ''), 50),
            'dt_filiacao_sinac' => Normalizer::toDateOrNull($fcfo['DATAOP3'] ?? null),
            // DATAOP1 é a data de abertura mantida pela ABAC; DTINICATIVIDADES
            // (início das atividades) só entra quando ela está vazia.
            'dt_abertura_empresa' => Normalizer::toDateOrNull($fcfo['DATAOP1'] ?? null)
                ?? Normalizer::toDateOrNull($fcfo['DTINICATIVIDADES'] ?? null),
        ];
    }

    /**
     * Preenche em cliente já existente só as colunas opcionais ainda vazias —
     * valor digitado no app (ou vindo de outra carga) nunca é sobrescrito.
     *
     * @param array<string,mixed> $fcfo
     */
    private function backfillOpcionais(
        int $clientId,
        array $fcfo,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        $atuais = $this->opcionaisAtuais[$clientId] ?? [];
        $update = [];

        foreach ($this->buildOpcionais($fcfo) as $coluna => $valor) {
            $atual = $atuais[$coluna] ?? null;

            if ($valor === null || trim((string) $atual) !== '') {
                continue;
            }

            $update[$coluna] = $valor;
        }

        if ($update === []) {
            return;
        }

        // DB::table em vez do model: backfill não é edição de usuário, não gera
        // auditoria e não mexe no updated_at do cadastro.
        if (! $options->dryRun) {
            DB::table('clients')->where('id', $clientId)->update($update);
        }

        foreach ($update as $coluna => $valor) {
            $report->backfillCampos[$coluna]++;
            $this->opcionaisAtuais[$clientId][$coluna] = $valor;
        }
    }

    /**
     * CAMPOALFAOP1 guarda o site da administradora. O destino é
     * client_redes_sociais (tipo 'site'), exibido em Cadastro > Informações da
     * empresa — `clients` não tem coluna de site.
     *
     * @param array<string,mixed> $fcfo
     * @return array<string,mixed>|null atributos da rede social, sem o client_id
     */
    private function resolveSite(array $fcfo, RmImportReport $report): ?array
    {
        $bruto = Normalizer::trimOrNull((string) ($fcfo['CAMPOALFAOP1'] ?? ''));

        if ($bruto === null) {
            return null;
        }

        $url = Normalizer::formatUrl($bruto);

        if ($url === null) {
            $report->sitesInvalidos++;
            $this->warn($report, 'CAMPOALFAOP1 não é um endereço de site — ignorado', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
                'valor' => $bruto,
            ]);

            return null;
        }

        return [
            'tipo' => 'site',
            'rotulo' => 'Site da empresa',
            'url' => Normalizer::limit($url, 500),
        ];
    }

    /**
     * Cria a rede social do site para o cliente que ainda não tem nenhuma do
     * tipo. Retorna true quando criou (ou criaria, no dry-run).
     *
     * @param array<string,mixed> $site
     */
    private function attachSite(int $clientId, array $site, RmImportOptions $options): bool
    {
        if (isset($this->siteExistente[$clientId])) {
            return false;
        }

        if (! $options->dryRun) {
            ClientRedeSocial::create($site + ['client_id' => $clientId]);
        }

        $this->siteExistente[$clientId] = true;

        return true;
    }

    /**
     * Os 3 endereços da FCFO casam 1:1 com client_enderecos.tipo.
     *
     * Em client_enderecos só `tipo` e `complemento` são nullable — cep, rua, numero,
     * bairro, pais, estado, cod_ibge e municipio são NOT NULL. Como o RM preenche
     * esses campos de forma esparsa, a ausência vira string vazia em vez de null.
     *
     * @param array<string,mixed> $fcfo
     * @return list<array<string,mixed>>
     */
    private function buildEnderecos(array $fcfo): array
    {
        $grupos = [
            'principal' => ['RUA', 'NUMERO', 'COMPLEMENTO', 'BAIRRO', 'CIDADE', 'CODETD', 'CEP', 'PAIS', 'CODMUNICIPIO'],
            'pagamento' => ['RUAPGTO', 'NUMEROPGTO', 'COMPLEMENTOPGTO', 'BAIRROPGTO', 'CIDADEPGTO', 'CODETDPGTO', 'CEPPGTO', 'PAISPAGTO', 'CODMUNICIPIOPGTO'],
            'entrega' => ['RUAENTREGA', 'NUMEROENTREGA', 'COMPLEMENTREGA', 'BAIRROENTREGA', 'CIDADEENTREGA', 'CODETDENTREGA', 'CEPENTREGA', 'PAISENTREGA', 'CODMUNICIPIOENTREGA'],
        ];

        $enderecos = [];

        foreach ($grupos as $tipo => [$rua, $numero, $complemento, $bairro, $cidade, $uf, $cep, $pais, $codMun]) {
            $ruaVal = Normalizer::trimOrNull((string) ($fcfo[$rua] ?? ''));
            $cepVal = Normalizer::formatCep((string) ($fcfo[$cep] ?? ''));
            $cidadeVal = Normalizer::trimOrNull((string) ($fcfo[$cidade] ?? ''));

            if ($ruaVal === null && $cepVal === null && $cidadeVal === null) {
                continue;
            }

            $ufVal = Normalizer::trimOrNull((string) ($fcfo[$uf] ?? ''));

            $enderecos[] = [
                'tipo' => $tipo,
                'cep' => $cepVal ?? '',
                'rua' => $ruaVal ?? '',
                'numero' => Normalizer::trimOrNull((string) ($fcfo[$numero] ?? '')) ?? '',
                // única coluna (além de tipo) que aceita null no destino
                'complemento' => Normalizer::trimOrNull((string) ($fcfo[$complemento] ?? '')),
                'bairro' => Normalizer::trimOrNull((string) ($fcfo[$bairro] ?? '')) ?? '',
                'pais' => Normalizer::trimOrNull((string) ($fcfo[$pais] ?? '')) ?? '',
                'estado' => $ufVal ?? '',
                'cod_ibge' => Normalizer::composeIbge($ufVal, (string) ($fcfo[$codMun] ?? '')) ?? '',
                'municipio' => $cidadeVal ?? '',
            ];
        }

        return $enderecos;
    }

    /**
     * @param list<array<string,mixed>> $contatos
     * @param array<string,array<string,mixed>> $complMap
     */
    private function processContatos(
        int $clientId,
        array $contatos,
        array $complMap,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        $keys = &$this->contactKeysFor($clientId);

        foreach ($contatos as $contato) {
            $emails = Normalizer::splitEmails((string) ($contato['EMAIL'] ?? ''));
            $nomeKey = Normalizer::normalizeName((string) ($contato['NOME'] ?? ''));

            if ($emails === [] && $nomeKey === '') {
                $report->contatosPuladosSemChave++;
                continue;
            }

            if ($emails !== [] && isset($keys['emails'][$emails[0]])) {
                $report->contatosPuladosEmail++;
                continue;
            }

            if ($emails === [] && isset($keys['names'][$nomeKey])) {
                $report->contatosPuladosNome++;
                continue;
            }

            $attrs = $this->buildContatoAttributes($clientId, $contato, $emails, $complMap);

            if (! $options->dryRun) {
                try {
                    ClientContato::create($attrs);
                } catch (Throwable $e) {
                    $report->erros++;
                    $this->warn($report, 'Falha ao criar contato — pulado', [
                        'client_id' => $clientId,
                        'nome' => $contato['NOME'] ?? null,
                        'erro' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            $report->contatosCriados++;

            foreach ($emails as $email) {
                $keys['emails'][$email] = true;
            }
            if ($nomeKey !== '') {
                $keys['names'][$nomeKey] = true;
            }
        }
    }

    /**
     * Conjunto de e-mails/nomes já existentes para o cliente (cacheado por execução).
     * Inclui os e-mails do próprio cadastro do cliente (email, email_2..email_7).
     *
     * @return array{emails:array<string,true>,names:array<string,true>}
     */
    private function &contactKeysFor(int $clientId): array
    {
        if (isset($this->contactKeys[$clientId])) {
            return $this->contactKeys[$clientId];
        }

        $emails = [];
        $names = [];

        foreach ($this->emailSeed[$clientId] ?? [] as $email) {
            $emails[$email] = true;
        }

        if ($clientId > 0) {
            $rows = DB::table('client_contatos')
                ->where('client_id', $clientId)
                ->get(['email', 'email_2', 'nome']);

            foreach ($rows as $row) {
                foreach ([$row->email, $row->email_2] as $email) {
                    $email = mb_strtolower(trim((string) $email));
                    if ($email !== '') {
                        $emails[$email] = true;
                    }
                }

                $nomeKey = Normalizer::normalizeName((string) $row->nome);
                if ($nomeKey !== '') {
                    $names[$nomeKey] = true;
                }
            }
        }

        $this->contactKeys[$clientId] = ['emails' => $emails, 'names' => $names];

        return $this->contactKeys[$clientId];
    }

    /**
     * @param array<string,mixed> $contato
     * @param list<string> $emails
     * @param array<string,array<string,mixed>> $complMap
     * @return array<string,mixed>
     */
    private function buildContatoAttributes(int $clientId, array $contato, array $emails, array $complMap): array
    {
        $obsParts = [];

        $ativo = $contato['ATIVO'] ?? null;
        if ($ativo !== null && (int) $ativo !== 1) {
            $obsParts[] = '[Inativo no RM]';
        }

        $observacao = Normalizer::trimOrNull((string) ($contato['OBSERVACAO'] ?? ''));
        if ($observacao !== null) {
            $obsParts[] = $observacao;
        }

        if (count($emails) > 2) {
            $obsParts[] = 'E-mails: ' . implode('; ', array_slice($emails, 2));
        }

        // Campos complementares custom (FCFOCONTATOCOMPL) anexados como texto.
        $complKey = ((int) $contato['CODCOLIGADA']) . '|' . trim((string) $contato['CODCFO']) . '|' . $contato['IDCONTATO'];
        foreach ($complMap[$complKey] ?? [] as $col => $valor) {
            if (in_array($col, ['CODCOLIGADA', 'CODCFO', 'IDCONTATO'], true)) {
                continue;
            }
            $valor = is_scalar($valor) ? Normalizer::trimOrNull((string) $valor) : null;
            if ($valor !== null) {
                $obsParts[] = "{$col}: {$valor}";
            }
        }

        return [
            'client_id' => $clientId,
            'nome' => Normalizer::limit((string) ($contato['NOME'] ?? ''), 255),
            'funcao' => Normalizer::limit((string) ($contato['FUNCAO'] ?? ''), 255),
            'email' => $emails[0] ?? null,
            'email_2' => $emails[1] ?? null,
            'telefone' => Normalizer::limit((string) ($contato['TELEFONE'] ?? ''), 255),
            'telefone_2' => Normalizer::limit((string) ($contato['FAX'] ?? ''), 255),
            'ramal' => Normalizer::limit((string) ($contato['RAMAL'] ?? ''), 30),
            'dt_nascimento' => Normalizer::toDateOrNull($contato['DATANASCIMENTO'] ?? null),
            'obs' => Normalizer::limit(implode(' | ', $obsParts), 255),
        ];
    }

    /**
     * Lê GCCUSTO e monta o mapa em memória "coligada|codigo" => atributos.
     * Nada do RM é persistido: coligada/código servem só para resolver o join
     * FCFODEF -> GCCUSTO durante a carga.
     */
    private function loadRmCentrosCusto(RmImportReport $report): void
    {
        foreach ($this->reader->allCentrosCusto() as $row) {
            $codigo = trim((string) ($row['CODCCUSTO'] ?? ''));
            if ($codigo === '') {
                $this->warn($report, 'GCCUSTO com CODCCUSTO vazio — ignorado', [
                    'coligada' => $row['CODCOLIGADA'] ?? null,
                ]);
                continue;
            }

            $key = ((int) ($row['CODCOLIGADA'] ?? 0)) . '|' . $codigo;

            $this->ccDetails[$key] = [
                'codigo' => Normalizer::limit($codigo, 30),
                'nome' => Normalizer::limit((string) ($row['NOME'] ?? ''), 150),
                'codigo_reduzido' => Normalizer::limit((string) ($row['CODREDUZIDO'] ?? ''), 30),
                'classificacao' => Normalizer::limit((string) ($row['CODCLASSIFICA'] ?? ''), 60),
                'ativo' => ($row['ATIVO'] ?? null) === null || (int) $row['ATIVO'] === 1,
                'permite_lancamentos' => ($row['PERMITELANC'] ?? null) === null || (int) $row['PERMITELANC'] === 1,
                'responsavel' => Normalizer::limit((string) ($row['RESPONSAVEL'] ?? ''), 120),
            ];
        }
    }

    /**
     * Carrega o estado do destino: documentos normalizados, e-mails do cadastro,
     * campos opcionais já preenchidos, centros de custo e sites já vinculados —
     * três queries leves.
     */
    private function loadClientMaps(RmImportReport $report): void
    {
        $rows = DB::table('clients')
            ->orderBy('id')
            ->get(array_merge(['id', 'document'], self::CLIENT_EMAIL_COLUMNS, self::CLIENT_OPCIONAIS_COLUMNS));

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $digits = Normalizer::digits((string) $row->document);

            if ($digits !== '') {
                if (isset($this->byDigits[$digits])) {
                    // Duplicata pré-existente no destino: o menor id vence (fora de escopo corrigir).
                    $this->warn($report, 'Documento duplicado já existente no destino — usando o menor id', [
                        'documento' => $digits,
                        'id_usado' => $this->byDigits[$digits],
                        'id_ignorado' => $id,
                    ]);
                } else {
                    $this->byDigits[$digits] = $id;
                }
            }

            $emails = [];
            foreach (self::CLIENT_EMAIL_COLUMNS as $col) {
                $email = mb_strtolower(trim((string) $row->{$col}));
                if ($email !== '') {
                    $emails[] = $email;
                }
            }
            if ($emails !== []) {
                $this->emailSeed[$id] = $emails;
            }

            $opcionais = [];
            foreach (self::CLIENT_OPCIONAIS_COLUMNS as $col) {
                $opcionais[$col] = $row->{$col};
            }
            $this->opcionaisAtuais[$id] = $opcionais;
        }

        foreach (DB::table('centros_custo')->get(['client_id', 'codigo']) as $cc) {
            $this->ccExisting[((int) $cc->client_id) . '|' . $cc->codigo] = true;
        }

        foreach (DB::table('client_redes_sociais')->where('tipo', 'site')->pluck('client_id') as $clientId) {
            $this->siteExistente[(int) $clientId] = true;
        }
    }

    /**
     * Resolve o centro de custo default (FCFODEF.CODCCUSTO) para os atributos da
     * linha a criar em centros_custo. Preferência: linha do FCFODEF da mesma
     * coligada do cli/for; lookup com fallback para a coligada do FCFO
     * (convenção RM de coligada 0 = global).
     *
     * @param array<string,mixed> $fcfo
     * @param list<array<string,mixed>> $defRows
     * @return array<string,mixed>|null
     */
    private function resolveCentroCusto(array $fcfo, array $defRows, RmImportReport $report): ?array
    {
        if ($defRows === []) {
            return null;
        }

        $fcfoColigada = (int) ($fcfo['CODCOLIGADA'] ?? 0);

        usort($defRows, static function (array $a, array $b) use ($fcfoColigada): int {
            $aMatch = ((int) ($a['CODCOLIGADA'] ?? -1)) === $fcfoColigada ? 0 : 1;
            $bMatch = ((int) ($b['CODCOLIGADA'] ?? -1)) === $fcfoColigada ? 0 : 1;

            return $aMatch <=> $bMatch ?: ((int) ($a['CODCOLIGADA'] ?? 0)) <=> ((int) ($b['CODCOLIGADA'] ?? 0));
        });

        foreach ($defRows as $def) {
            $codigo = trim((string) ($def['CODCCUSTO'] ?? ''));
            if ($codigo === '') {
                continue;
            }

            $defColigada = (int) ($def['CODCOLIGADA'] ?? 0);
            $cc = $this->ccDetails[$defColigada . '|' . $codigo]
                ?? $this->ccDetails[$fcfoColigada . '|' . $codigo]
                ?? null;

            if ($cc !== null) {
                return $cc;
            }

            $this->warn($report, 'Centro de custo do FCFODEF não encontrado no GCCUSTO', [
                'coligada' => $fcfo['CODCOLIGADA'] ?? null,
                'codcfo' => $fcfo['CODCFO'] ?? null,
                'codccusto' => $codigo,
            ]);
        }

        return null;
    }

    private function mapPagRec(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ((int) $value) {
            1 => 'Fornecedor',
            2 => 'Cliente',
            3 => 'Cliente/Fornecedor',
            default => null,
        };
    }

    /**
     * @param array<string,mixed> $context
     */
    private function warn(RmImportReport $report, string $message, array $context = []): void
    {
        $report->warn($message, $context);
        $this->logger->warning('rm.import.warn: ' . $message, $context);
    }
}
