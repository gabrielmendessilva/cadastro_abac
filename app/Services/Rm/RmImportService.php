<?php

namespace App\Services\Rm;

use App\Models\CentroCusto;
use App\Models\Client;
use App\Models\ClientComite;
use App\Models\ClientContato;
use App\Models\ClientEndereco;
use App\Models\ClientRedeSocial;
use App\Services\Rm\Contracts\RmReaderInterface;
use App\Services\Rm\Exceptions\RmImportException;
use App\Services\Rm\Support\Normalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
 * - `status`: quem está no RM sem FCFOCOMPL.STATUS = 'OK' E OCORRENCIA = 'OK'
 *   fica desativado no app. É a única coluna de cliente existente que a carga
 *   sobrescreve, e só num sentido — cadastro fora de ordem no RM desativa, mas
 *   cadastro em ordem nunca reativa o que foi desativado à mão aqui. Cliente que
 *   não tem CNPJ no RM não é tocado.
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

    /**
     * Colunas de clients cuja única fonte é o RM (campos da aba "Opcionais" e o
     * tipo de cli/for). São as que o backfill completa em cliente já existente.
     */
    private const CLIENT_RM_COLUMNS = [
        'num_filiacao_abac', 'dt_filiacao_abac',
        'num_filiacao_sinac', 'dt_filiacao_sinac',
        'dt_abertura_empresa', 'categoria',
        'situacao_abac', 'ocorrencia_abac',
    ];

    /** Idem para client_contatos: colunas alimentadas por FCFOCONTATO/FCFOCONTATOCOMPL. */
    private const CONTATO_RM_COLUMNS = [
        'dt_nascimento', 'aniversario', 'celular',
        'departamento', 'outro_departamento', 'representante_legal', 'comite',
    ];

    /**
     * Colunas de FCFOCONTATOCOMPL que já têm destino próprio — as demais (custom
     * de cada instalação) continuam indo para o texto de `obs`.
     */
    private const CONTATO_COMPL_MAPEADAS = [
        'CODCOLIGADA', 'CODCFO', 'IDCONTATO',
        'DEPTO', 'OUTROS', 'REPRESENTANTE', 'COMITE', 'ANIV',
    ];

    /** "COMITÊ ", "COMITE ", "COMTE ", "COMTIÊ " — o RM tem as quatro grafias. */
    private const PREFIXO_COMITE = '/^\s*COM[IT]{1,3}[EÊ]?\s+/iu';

    /** O RM às vezes embute o papel no nome: "COORDENADORA COMITE ANTIFRAUDES". */
    private const PREFIXO_COORDENADOR = '/^\s*COORDENADOR(A|ES|AS)?\s+/iu';

    /**
     * Dedup de contatos por cliente. `emails`/`names` apontam para o id do contato
     * no destino (0 quando é só semente do cadastro do cliente, sem linha própria),
     * e `atuais` guarda o valor corrente das colunas alimentadas pelo RM.
     *
     * @var array<int|string,array{emails:array<string,int>,names:array<string,int>,atuais:array<int,array<string,mixed>>}>
     */
    private array $contactKeys = [];

    /** @var array<string,true> documentos já vistos nesta execução (detecção de duplicado no RM) */
    private array $rmSeenDigits = [];

    /** @var array<string,array<string,mixed>> "coligada|codigo" (só em memória) => atributos do centro de custo */
    private array $ccDetails = [];

    /** @var array<string,true> "clientId|codigo" => centro de custo já existente/criado */
    private array $ccExisting = [];

    /** @var array<int,array<string,mixed>> clients.id => valor atual das colunas de CLIENT_RM_COLUMNS */
    private array $camposRmAtuais = [];

    /**
     * clients.id => true quando ALGUMA linha do RM daquele CNPJ veio com
     * STATUS e OCORRENCIA = 'OK'. O "alguma" importa: o mesmo CNPJ aparece
     * repetido no RM e basta um cadastro em ordem para a empresa continuar ativa.
     *
     * @var array<int,bool>
     */
    private array $emOrdemNoRm = [];

    /** @var array<int,bool> clients.id => status corrente no destino (true = ativo) */
    private array $statusAtual = [];

    /** @var array<int,true> clients.id que já têm rede social do tipo 'site' */
    private array $siteExistente = [];

    /** @var array<string,string> "coligada|codtcf" => descrição do tipo de cli/for (FTCF) */
    private array $tiposCliFor = [];

    /** @var array<string,string> nome normalizado => nome oficial na lista de domínio `comites` */
    private array $comitesDominio = [];

    /** @var array<string,true> "clientId|contatoId|comite_nome" já existente/criado */
    private array $comitesExistentes = [];

    private int $fakeId = 0;

    private int $fakeContatoId = 0;

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
        $this->preflightDestino();

        $this->loadRmCentrosCusto($report);
        $this->loadRmTiposCliFor();
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
                    $fcfoComplMap = $this->reader->complementaresForKeys($keys);
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
                                $fcfoComplMap[$key] ?? [],
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

        $this->desativaForaDeOrdem($options, $report);

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
        $this->camposRmAtuais = [];
        $this->emOrdemNoRm = [];
        $this->statusAtual = [];
        $this->siteExistente = [];
        $this->tiposCliFor = [];
        $this->comitesDominio = [];
        $this->comitesExistentes = [];
        $this->fakeId = 0;
        $this->fakeContatoId = 0;
    }

    /**
     * @param array<string,mixed> $fcfo
     * @param list<array<string,mixed>> $contatos
     * @param list<array<string,mixed>> $defRows
     * @param array<string,mixed> $fcfoCompl linha de FCFOCOMPL do cli/for
     * @param array<string,array<string,mixed>> $complMap
     */
    private function processFcfoRow(
        array $fcfo,
        array $contatos,
        array $defRows,
        array $fcfoCompl,
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

                $this->backfillCamposRm($clientId, $fcfo, $fcfoCompl, $options, $report);

                $site = $this->resolveSite($fcfo, $report);

                if ($site !== null && $this->attachSite($clientId, $site, $options)) {
                    $report->redesSociaisCriadas++;
                }
            }
        } else {
            $clientId = $this->createClient($fcfo, $digits, $defRows, $fcfoCompl, $options, $report);
        }

        $this->emOrdemNoRm[$clientId] = ($this->emOrdemNoRm[$clientId] ?? false)
            || $this->cadastroEmOrdem($fcfoCompl);

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
        array $fcfoCompl,
        RmImportOptions $options,
        RmImportReport $report,
    ): int {
        $cc = $this->resolveCentroCusto($fcfo, $defRows, $report);
        $attrs = $this->buildClientAttributes($fcfo, $digits, $fcfoCompl, $report);
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
        // Sem ATIVO no RM vale o default do banco (tinyint(1) NOT NULL default 1).
        $this->statusAtual[$clientId] = (bool) ($attrs['status'] ?? true);

        // Duplicado do mesmo documento mais adiante no RM cai no caminho de
        // backfill: semeia o que já foi gravado para ele só completar buracos.
        $this->camposRmAtuais[$clientId] = array_intersect_key(
            $attrs,
            array_flip(self::CLIENT_RM_COLUMNS),
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
    private function buildClientAttributes(array $fcfo, string $digits, array $fcfoCompl, RmImportReport $report): array
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
        ] + $this->buildCamposRm($fcfo, $fcfoCompl);

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
     * Colunas de clients que só o RM tem.
     *
     * Campos livres da aba "Opcionais", como a ABAC os usa: CAMPOALFAOP2/DATAOP2
     * = filiação ABAC, CAMPOALFAOP3/DATAOP3 = filiação SINAC e DATAOP1 = data de
     * abertura. Mais o tipo de cli/for (CODTCF), taxonomia onde ficam as
     * categorias de sócio especial.
     *
     * @param array<string,mixed> $fcfo
     * @return array<string,string|null> colunas de CLIENT_RM_COLUMNS
     */
    private function buildCamposRm(array $fcfo, array $fcfoCompl): array
    {
        return [
            'categoria' => Normalizer::limit($this->resolveTipoCliFor($fcfo), 100),
            // Siglas do RM sem dicionário na origem (OK, CA, FL, LE...): entram
            // como vieram. O par STATUS+OCORRENCIA = 'OK' é o recorte de
            // "cadastro em ordem" que a secretaria usava nos relatórios.
            'situacao_abac' => Normalizer::limit((string) ($fcfoCompl['STATUS'] ?? ''), 100),
            'ocorrencia_abac' => Normalizer::limit((string) ($fcfoCompl['OCORRENCIA'] ?? ''), 20),
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
     * Descrição do tipo de cli/for (FTCF), com fallback para o próprio código:
     * em 92 dos 97 tipos os dois são iguais, mas o rótulo é o que vale na tela.
     *
     * @param array<string,mixed> $fcfo
     */
    private function resolveTipoCliFor(array $fcfo): ?string
    {
        $codigo = Normalizer::trimOrNull((string) ($fcfo['CODTCF'] ?? ''));

        if ($codigo === null) {
            return null;
        }

        $coligada = (int) ($fcfo['CODCOLTCF'] ?? $fcfo['CODCOLIGADA'] ?? 0);

        return $this->tiposCliFor[$coligada . '|' . $codigo] ?? $codigo;
    }

    /**
     * Preenche em cliente já existente só as colunas do RM ainda vazias —
     * valor digitado no app (ou vindo de outra carga) nunca é sobrescrito.
     *
     * @param array<string,mixed> $fcfo
     */
    private function backfillCamposRm(
        int $clientId,
        array $fcfo,
        array $fcfoCompl,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        $atuais = $this->camposRmAtuais[$clientId] ?? [];
        $update = [];

        foreach ($this->buildCamposRm($fcfo, $fcfoCompl) as $coluna => $valor) {
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
            $this->camposRmAtuais[$clientId][$coluna] = $valor;
        }
    }

    /**
     * "Cadastro em ordem" no RM: FCFOCOMPL.STATUS = 'OK' E OCORRENCIA = 'OK' —
     * o mesmo par que a secretaria usava para recortar os relatórios.
     *
     * Cli/for sem linha na FCFOCOMPL cai no mesmo saco de quem tem o par vazio:
     * o cadastro não está em ordem. A comparação é frouxa de propósito (aparam-se
     * espaços e o caixa varia na origem: 'OK', 'ok', 'Ok ').
     *
     * @param array<string,mixed> $fcfoCompl linha de FCFOCOMPL do cli/for
     */
    private function cadastroEmOrdem(array $fcfoCompl): bool
    {
        foreach (['STATUS', 'OCORRENCIA'] as $coluna) {
            if (mb_strtoupper(trim((string) ($fcfoCompl[$coluna] ?? ''))) !== 'OK') {
                return false;
            }
        }

        return true;
    }

    /**
     * Desativa quem está no RM com o cadastro fora de ordem.
     *
     * Roda uma vez no fim porque o mesmo CNPJ aparece em mais de uma linha do RM
     * (coligadas diferentes) e a decisão só fecha depois de ver todas: basta uma
     * linha em ordem para a empresa continuar ativa.
     *
     * Só desativa — cadastro em ordem não reativa nada. Reativar sobrescreveria a
     * desativação feita à mão no app, que é decisão de quem cuida do cadastro.
     * Cliente sem CNPJ no RM não entra no mapa e portanto não é tocado.
     */
    private function desativaForaDeOrdem(RmImportOptions $options, RmImportReport $report): void
    {
        if (! $options->desativarForaDeOrdem) {
            return;
        }

        $ids = [];

        foreach ($this->emOrdemNoRm as $clientId => $emOrdem) {
            if ($emOrdem || ($this->statusAtual[$clientId] ?? true) === false) {
                continue;
            }

            $ids[] = $clientId;
            $this->statusAtual[$clientId] = false;
        }

        $report->clientsDesativados = count($ids);

        if ($ids === [] || $options->dryRun) {
            return;
        }

        // DB::table em vez do model, como no backfill: correção vinda da carga não
        // é edição de usuário — não gera auditoria e não mexe no updated_at.
        // Em lotes: a lista pode ter milhares de ids e o driver tem teto de
        // parâmetros por query.
        foreach (array_chunk($ids, 500) as $lote) {
            DB::table('clients')->whereIn('id', $lote)->update(['status' => false]);
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
            $compl = $complMap[$this->complKey($contato)] ?? [];

            if ($emails === [] && $nomeKey === '') {
                $report->contatosPuladosSemChave++;
                continue;
            }

            $existenteId = null;

            if ($emails !== [] && isset($keys['emails'][$emails[0]])) {
                $report->contatosPuladosEmail++;
                $existenteId = $keys['emails'][$emails[0]];
            } elseif ($emails === [] && isset($keys['names'][$nomeKey])) {
                $report->contatosPuladosNome++;
                $existenteId = $keys['names'][$nomeKey];
            }

            if ($existenteId !== null) {
                // Contato já cadastrado não é reescrito: só ganha as colunas do RM
                // que continuam vazias e os comitês que ainda não tem. Id 0 é
                // semente do e-mail do próprio cliente — não há linha para completar.
                if ($options->backfill && $existenteId > 0) {
                    $this->backfillContato($existenteId, $clientId, $contato, $compl, $keys, $options, $report);
                }

                continue;
            }

            $attrs = $this->buildContatoAttributes($clientId, $contato, $emails, $compl);
            $contatoId = --$this->fakeContatoId;

            if (! $options->dryRun) {
                try {
                    $contatoId = (int) ClientContato::create($attrs)->id;
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

            $keys['atuais'][$contatoId] = array_intersect_key($attrs, array_flip(self::CONTATO_RM_COLUMNS));

            foreach ($emails as $email) {
                $keys['emails'][$email] = $contatoId;
            }
            if ($nomeKey !== '') {
                $keys['names'][$nomeKey] = $contatoId;
            }

            $this->attachComites($clientId, $contatoId, $compl, $options, $report);
        }
    }

    /**
     * Completa um contato que já existe no destino: só colunas alimentadas pelo
     * RM e só quando estão vazias. Os comitês entram sempre que faltarem, porque
     * vivem em tabela satélite própria.
     *
     * @param array<string,mixed> $contato
     * @param array<string,mixed> $compl
     * @param array{emails:array<string,int>,names:array<string,int>,atuais:array<int,array<string,mixed>>} $keys
     */
    private function backfillContato(
        int $contatoId,
        int $clientId,
        array $contato,
        array $compl,
        array &$keys,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        $atuais = $keys['atuais'][$contatoId] ?? [];
        $update = [];

        foreach ($this->buildCamposRmContato($contato, $compl) as $coluna => $valor) {
            if ($valor === null || trim((string) ($atuais[$coluna] ?? '')) !== '') {
                continue;
            }

            $update[$coluna] = $valor;
        }

        if ($update !== []) {
            if (! $options->dryRun) {
                DB::table('client_contatos')->where('id', $contatoId)->update($update);
            }

            foreach ($update as $coluna => $valor) {
                $report->backfillContato[$coluna]++;
                $keys['atuais'][$contatoId][$coluna] = $valor;
            }
        }

        $this->attachComites($clientId, $contatoId, $compl, $options, $report);
    }

    /**
     * Estado dos contatos do cliente (cacheado por execução): e-mails e nomes já
     * usados apontando para o id do contato, mais o valor atual das colunas que o
     * RM alimenta. Os e-mails do próprio cadastro do cliente entram com id 0 —
     * bloqueiam a criação de um contato repetido, mas não são linha de contato.
     *
     * @return array{emails:array<string,int>,names:array<string,int>,atuais:array<int,array<string,mixed>>}
     */
    private function &contactKeysFor(int $clientId): array
    {
        if (isset($this->contactKeys[$clientId])) {
            return $this->contactKeys[$clientId];
        }

        $emails = [];
        $names = [];
        $atuais = [];

        foreach ($this->emailSeed[$clientId] ?? [] as $email) {
            $emails[$email] = 0;
        }

        if ($clientId > 0) {
            $rows = DB::table('client_contatos')
                ->where('client_id', $clientId)
                ->get(array_merge(['id', 'email', 'email_2', 'nome'], self::CONTATO_RM_COLUMNS));

            foreach ($rows as $row) {
                $id = (int) $row->id;

                foreach ([$row->email, $row->email_2] as $email) {
                    $email = mb_strtolower(trim((string) $email));
                    if ($email !== '') {
                        $emails[$email] = $id;
                    }
                }

                $nomeKey = Normalizer::normalizeName((string) $row->nome);
                if ($nomeKey !== '') {
                    $names[$nomeKey] = $id;
                }

                $valores = [];
                foreach (self::CONTATO_RM_COLUMNS as $col) {
                    $valores[$col] = $row->{$col};
                }
                $atuais[$id] = $valores;
            }
        }

        $this->contactKeys[$clientId] = ['emails' => $emails, 'names' => $names, 'atuais' => $atuais];

        return $this->contactKeys[$clientId];
    }

    /**
     * @param array<string,mixed> $contato
     * @param list<string> $emails
     * @param array<string,mixed> $compl linha de FCFOCONTATOCOMPL do contato
     * @return array<string,mixed>
     */
    private function buildContatoAttributes(int $clientId, array $contato, array $emails, array $compl): array
    {
        $obsParts = [];

        $ativo = $contato['ATIVO'] ?? null;
        if ($ativo !== null && (int) $ativo !== 1) {
            $obsParts[] = '[Inativo no RM]';
        }

        // OBSERVACAO que é dd/mm virou o campo Aniversário; só o resto é texto.
        $observacao = Normalizer::trimOrNull((string) ($contato['OBSERVACAO'] ?? ''));
        if ($observacao !== null && $this->aniversarioDeTexto($observacao) === null) {
            $obsParts[] = $observacao;
        }

        if (count($emails) > 2) {
            $obsParts[] = 'E-mails: ' . implode('; ', array_slice($emails, 2));
        }

        // Campo complementar custom sem coluna própria no destino segue como texto.
        foreach ($compl as $col => $valor) {
            if (in_array($col, self::CONTATO_COMPL_MAPEADAS, true)) {
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
            'ramal' => Normalizer::limit((string) ($contato['RAMAL'] ?? ''), 30),
            'obs' => Normalizer::limit(implode(' | ', $obsParts), 255),
        ] + $this->buildCamposRmContato($contato, $compl);
    }

    /**
     * Colunas de client_contatos alimentadas pelo RM.
     *
     * FAX guarda celular nesta base (1.762 dos 1.929 valores começam com 9), daí
     * ele ir para `celular` e não para um segundo telefone. DEPTO/OUTROS/
     * REPRESENTANTE vêm da FCFOCONTATOCOMPL, e `comite` é só a marcação — os
     * comitês em si viram linhas em client_comites.
     *
     * @param array<string,mixed> $contato
     * @param array<string,mixed> $compl
     * @return array<string,string|null> colunas de CONTATO_RM_COLUMNS
     */
    private function buildCamposRmContato(array $contato, array $compl): array
    {
        $representante = mb_strtoupper((string) $this->complValor($compl, 'REPRESENTANTE'));
        $outros = $this->complValor($compl, 'OUTROS');

        return [
            'dt_nascimento' => Normalizer::toDateOrNull($contato['DATANASCIMENTO'] ?? null)
                ?? Normalizer::toDateOrNull($compl['ANIV'] ?? null),
            'aniversario' => $this->resolveAniversario($contato, $compl),
            'celular' => Normalizer::limit((string) ($contato['FAX'] ?? ''), 30),
            'departamento' => Normalizer::limit($this->complValor($compl, 'DEPTO'), 255),
            // OUTROS repete o comitê em 876 das 1.153 linhas — só o que não é
            // comitê é departamento de verdade.
            'outro_departamento' => $outros !== null && ! $this->pareceComite($outros)
                ? Normalizer::limit($outros, 255)
                : null,
            'representante_legal' => in_array($representante, ['S', '1', 'SIM'], true) ? '1' : null,
            'comite' => $this->resolveComites($compl) !== [] ? '1' : null,
        ];
    }

    /**
     * Aniversário do contato (dd/mm). A fonte é FCFOCONTATO.OBSERVACAO, que nesta
     * base guarda exatamente isso; FCFOCONTATOCOMPL.ANIV, quando existe, é data
     * completa e entra só como reserva.
     *
     * @param array<string,mixed> $contato
     * @param array<string,mixed> $compl
     */
    private function resolveAniversario(array $contato, array $compl): ?string
    {
        $doTexto = $this->aniversarioDeTexto(Normalizer::trimOrNull((string) ($contato['OBSERVACAO'] ?? '')));

        if ($doTexto !== null) {
            return $doTexto;
        }

        $aniv = Normalizer::toDateOrNull($compl['ANIV'] ?? null);

        return $aniv !== null ? date('d/m', (int) strtotime($aniv)) : null;
    }

    /** "16/09", "1/10" e "20/2" viram 16/09, 01/10 e 20/02; o resto é null. */
    private function aniversarioDeTexto(?string $valor): ?string
    {
        if ($valor === null || preg_match('#^(\d{1,2})\s*/\s*(\d{1,2})$#', $valor, $m) !== 1) {
            return null;
        }

        $dia = (int) $m[1];
        $mes = (int) $m[2];

        if ($dia < 1 || $dia > 31 || $mes < 1 || $mes > 12) {
            return null;
        }

        return sprintf('%02d/%02d', $dia, $mes);
    }

    /**
     * Comitês do contato. O RM guarda em FCFOCONTATOCOMPL.COMITE, com mais de um
     * separado por "/". Quando essa coluna está vazia, o mesmo dado às vezes ficou
     * em OUTROS ("COMITE JURÍDICO") — daí a reserva.
     *
     * @param array<string,mixed> $compl
     * @return list<array{nome:string,papel:string}> nome oficial da lista de domínio (ou o do RM)
     */
    private function resolveComites(array $compl): array
    {
        $bruto = $this->complValor($compl, 'COMITE');

        if ($bruto === null) {
            $outros = $this->complValor($compl, 'OUTROS');
            $bruto = $outros !== null && $this->pareceComite($outros) ? $outros : null;
        }

        if ($bruto === null) {
            return [];
        }

        $comites = [];

        foreach (preg_split('#[/;]#u', $bruto) ?: [] as $parte) {
            // O papel só aparece quando o RM o escreveu junto do nome; o resto
            // fica em titular, que é o default da coluna.
            $papel = preg_match(self::PREFIXO_COORDENADOR, $parte) === 1 ? 'coordenador' : 'titular';
            $nome = $this->resolveNomeComite(preg_replace(self::PREFIXO_COORDENADOR, '', $parte) ?? $parte);

            if ($nome === null || in_array($nome, array_column($comites, 'nome'), true)) {
                continue;
            }

            $comites[] = ['nome' => $nome, 'papel' => $papel];
        }

        return $comites;
    }

    /**
     * Casa o nome do RM com a lista de domínio `comites`: exato, tolerando
     * singular/plural (ANTIFRAUDE/ANTIFRAUDES) e por prefixo único
     * (MARKETING -> Comitê Marketing Institucional). Sem casar, o nome do RM
     * é mantido como veio para não perder o dado.
     */
    private function resolveNomeComite(string $bruto): ?string
    {
        $bruto = Normalizer::trimOrNull($bruto);

        if ($bruto === null) {
            return null;
        }

        $chave = self::chaveComite($bruto);

        if ($chave === '') {
            return null;
        }

        if (isset($this->comitesDominio[$chave])) {
            return $this->comitesDominio[$chave];
        }

        $variante = str_ends_with($chave, 'S') ? substr($chave, 0, -1) : $chave . 'S';
        if (isset($this->comitesDominio[$variante])) {
            return $this->comitesDominio[$variante];
        }

        $prefixados = array_filter(
            $this->comitesDominio,
            static fn (string $k): bool => str_starts_with($k, $chave),
            ARRAY_FILTER_USE_KEY,
        );

        if (count($prefixados) === 1) {
            return reset($prefixados);
        }

        return Normalizer::limit($bruto, 255);
    }

    /** Texto que começa com "COMITÊ ", "COMITE ", "COMTE "... é nome de comitê. */
    private function pareceComite(string $valor): bool
    {
        return preg_match(self::PREFIXO_COMITE, $valor) === 1;
    }

    /** Chave de comparação de comitê: sem prefixo "Comitê", sem acento e sem pontuação. */
    private static function chaveComite(string $valor): string
    {
        $semPrefixo = preg_replace(self::PREFIXO_COMITE, '', trim($valor)) ?? $valor;
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT', $semPrefixo) ?: $semPrefixo;

        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($semAcento)) ?? '';
    }

    /**
     * Cria os vínculos de comitê que ainda não existem para o contato.
     *
     * @param array<string,mixed> $compl
     */
    private function attachComites(
        int $clientId,
        int $contatoId,
        array $compl,
        RmImportOptions $options,
        RmImportReport $report,
    ): void {
        foreach ($this->resolveComites($compl) as ['nome' => $nome, 'papel' => $papel]) {
            $chave = $clientId . '|' . $contatoId . '|' . $nome;

            if (isset($this->comitesExistentes[$chave])) {
                continue;
            }

            if (! $options->dryRun) {
                try {
                    ClientComite::create([
                        'client_id' => $clientId,
                        'contato_id' => $contatoId,
                        'comite_nome' => $nome,
                        'papel' => $papel,
                        'observacoes' => $papel === 'titular'
                            ? 'Importado do TOTVS RM — papel não informado na origem.'
                            : 'Importado do TOTVS RM.',
                    ]);
                } catch (UniqueConstraintViolationException) {
                    // Outra execução da carga criou o mesmo vínculo: o dedup em
                    // memória é por processo, o índice único é que garante a
                    // regra. Nada a fazer — o vínculo já existe.
                    $this->comitesExistentes[$chave] = true;

                    continue;
                }
            }

            $this->comitesExistentes[$chave] = true;
            $report->comitesCriados++;
        }
    }

    /** Valor de texto de uma coluna da FCFOCONTATOCOMPL (COMITE é text no RM). */
    private function complValor(array $compl, string $coluna): ?string
    {
        $valor = $compl[$coluna] ?? null;

        return is_scalar($valor) ? Normalizer::trimOrNull((string) $valor) : null;
    }

    /** @param array<string,mixed> $contato */
    private function complKey(array $contato): string
    {
        return ((int) $contato['CODCOLIGADA']) . '|' . trim((string) $contato['CODCFO']) . '|' . $contato['IDCONTATO'];
    }

    /**
     * O banco vivo nem sempre está na mesma versão das migrations do repositório.
     * Falha cedo e com recado claro em vez de estourar SQLSTATE 42S22 no meio da
     * carga (foi assim que `aniversario` entrou: coluna nova em client_contatos).
     */
    private function preflightDestino(): void
    {
        $esperado = [
            'clients' => self::CLIENT_RM_COLUMNS,
            'client_contatos' => self::CONTATO_RM_COLUMNS,
        ];

        foreach ($esperado as $tabela => $colunas) {
            $faltando = array_values(array_diff($colunas, Schema::getColumnListing($tabela)));

            if ($faltando !== []) {
                throw RmImportException::destinoDesatualizado($tabela, $faltando);
            }
        }
    }

    /**
     * Mapa em memória "coligada|codtcf" => descrição do tipo de cli/for (FTCF).
     * Nada do RM é persistido: os códigos servem só para resolver o rótulo.
     */
    private function loadRmTiposCliFor(): void
    {
        foreach ($this->reader->allTiposCliFor() as $row) {
            $codigo = Normalizer::trimOrNull((string) ($row['CODTCF'] ?? ''));
            $descricao = Normalizer::trimOrNull((string) ($row['DESCRICAO'] ?? ''));

            if ($codigo === null || $descricao === null) {
                continue;
            }

            $this->tiposCliFor[((int) ($row['CODCOLIGADA'] ?? 0)) . '|' . $codigo] = $descricao;
        }
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
            ->get(array_merge(['id', 'document', 'status'], self::CLIENT_EMAIL_COLUMNS, self::CLIENT_RM_COLUMNS));

        foreach ($rows as $row) {
            $id = (int) $row->id;
            $digits = Normalizer::digits((string) $row->document);

            $this->statusAtual[$id] = (bool) $row->status;

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
            foreach (self::CLIENT_RM_COLUMNS as $col) {
                $opcionais[$col] = $row->{$col};
            }
            $this->camposRmAtuais[$id] = $opcionais;
        }

        foreach (DB::table('centros_custo')->get(['client_id', 'codigo']) as $cc) {
            $this->ccExisting[((int) $cc->client_id) . '|' . $cc->codigo] = true;
        }

        foreach (DB::table('client_redes_sociais')->where('tipo', 'site')->pluck('client_id') as $clientId) {
            $this->siteExistente[(int) $clientId] = true;
        }

        // Lista de domínio dos comitês: o RM escreve o nome à mão, com grafias e
        // acentuação variadas, e o app tem o vocabulário oficial.
        foreach (DB::table('comites')->pluck('nome') as $nome) {
            $this->comitesDominio[self::chaveComite((string) $nome)] = (string) $nome;
        }

        foreach (DB::table('client_comites')->get(['client_id', 'contato_id', 'comite_nome']) as $cm) {
            $this->comitesExistentes[
                ((int) $cm->client_id) . '|' . ((int) $cm->contato_id) . '|' . $cm->comite_nome
            ] = true;
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
