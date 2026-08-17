<?php

namespace App\Services\Aniversariantes;

use App\Models\ClientContato;
use App\Models\ClientEndereco;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Aniversariantes do mês — a versão no banco do app da consulta que a secretaria
 * rodava direto no RM:
 *
 *   ... FROM FCFO F JOIN FCFOCOMPL FC ... JOIN FCFOCONTATO F1 ...
 *   WHERE (MONTH(F1.DATANASCIMENTO) = '07' OR F1.OBSERVACAO LIKE '%/07')
 *
 * O de-para: FCFO -> clients (+ client_enderecos), FCFOCONTATO -> client_contatos,
 * F1.DATANASCIMENTO -> dt_nascimento, F1.OBSERVACAO -> aniversario (dd/mm),
 * F.CODTCF -> categoria, FC.ASSOCIADO -> associado_abac.
 *
 * O filtro `FCFOCOMPL.STATUS = 'OK' AND OCORRENCIA = 'OK'` da consulta original
 * chega aqui por outro caminho: a carga do RM (rm:import) já desativa quem está
 * no RM sem esse par em ordem, e esta lista só traz contato de empresa ativa.
 * Por isso não há filtro de status na tela — inativa não entra, ponto.
 *
 * O "OR" da origem é fiel: contato sem data de nascimento completa entra pelo
 * aniversário dd/mm, que para ~1.345 contatos é a única informação que existe.
 */
final class AniversariantesQuery
{
    /** Ordem de preferência do endereço exibido, como no resto do app. */
    private const PRIORIDADE_ENDERECO = ['principal' => 0, 'pagamento' => 1, 'entrega' => 2];

    public const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    public function __construct(
        public readonly int $mes,
        /** null = todos; true/false = só associados / só não associados */
        public readonly ?bool $associado = null,
        public readonly ?string $busca = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $mes = (int) $request->input('mes', now()->month);

        return new self(
            mes: isset(self::MESES[$mes]) ? $mes : (int) now()->month,
            associado: $request->filled('associado') ? $request->input('associado') === '1' : null,
            busca: $request->filled('busca') ? trim((string) $request->input('busca')) : null,
        );
    }

    public function nomeDoMes(): string
    {
        return self::MESES[$this->mes];
    }

    /**
     * Uma linha por contato aniversariante, já com empresa e endereço resolvidos.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function get(): Collection
    {
        $sufixo = '%/' . sprintf('%02d', $this->mes);

        $contatos = ClientContato::query()
            ->select('client_contatos.*')
            ->join('clients', 'clients.id', '=', 'client_contatos.client_id')
            ->with(['client.enderecos'])
            // Empresa desativada não gera aniversariante: quem saiu do quadro (ou
            // está fora de ordem no RM) não entra na lista nem na exportação.
            ->where('clients.status', true)
            ->where(function ($query) use ($sufixo) {
                $query->whereMonth('client_contatos.dt_nascimento', $this->mes)
                    ->orWhere('client_contatos.aniversario', 'like', $sufixo);
            })
            ->when($this->associado !== null, fn ($q) => $q->where('clients.associado_abac', $this->associado))
            ->when($this->busca !== null, function ($query) {
                $termo = '%' . $this->busca . '%';

                $query->where(function ($sub) use ($termo) {
                    $sub->where('clients.name', 'like', $termo)
                        ->orWhere('clients.fantasy_name', 'like', $termo)
                        ->orWhere('client_contatos.nome', 'like', $termo)
                        ->orWhere('client_contatos.email', 'like', $termo);
                });
            })
            ->get();

        return $contatos
            ->map(fn (ClientContato $contato): array => $this->linha($contato))
            // Ordena no PHP: a chave é dia do mês, que sai ora do dd/mm ora da
            // data completa, e montar isso em SQL amarraria a consulta ao MySQL.
            ->sortBy([
                fn (array $a, array $b) => ($a['dia'] ?? 99) <=> ($b['dia'] ?? 99),
                fn (array $a, array $b) => strcasecmp((string) $a['empresa'], (string) $b['empresa']),
                fn (array $a, array $b) => strcasecmp((string) $a['contato'], (string) $b['contato']),
            ])
            ->values();
    }

    /**
     * @return array<string,mixed>
     */
    private function linha(ClientContato $contato): array
    {
        $client = $contato->client;
        $endereco = $this->enderecoPreferido($client?->enderecos);
        [$dia, $mes] = $this->diaEMes($contato);

        return [
            'empresa' => $client?->name,
            'nome_fantasia' => $client?->fantasy_name,
            'rua' => $endereco?->rua,
            'numero' => $endereco?->numero,
            'complemento' => $endereco?->complemento,
            'bairro' => $endereco?->bairro,
            'cidade' => $endereco?->municipio,
            'uf' => $endereco?->estado,
            'cep' => $endereco?->cep,
            'telefone_empresa' => $client?->phone,
            'contato' => $contato->nome,
            'funcao' => $contato->funcao,
            'departamento' => $contato->departamento,
            'email' => $contato->email,
            'telefone_contato' => $contato->telefone,
            'celular' => $contato->celular,
            'dia' => $dia,
            'mes' => $mes,
            'dt_nascimento' => $contato->dt_nascimento?->format('Y-m-d'),
            'aniversario' => $contato->aniversario,
            'categoria' => $client?->categoria,
            'associado_abac' => (bool) $client?->associado_abac,
        ];
    }

    /**
     * Dia e mês do aniversário. O campo dd/mm manda quando existe — é o que a
     * secretaria mantém à mão; a data de nascimento entra como reserva.
     *
     * @return array{0:?int,1:?int}
     */
    private function diaEMes(ClientContato $contato): array
    {
        if ($contato->aniversario !== null && preg_match('#^(\d{2})/(\d{2})$#', $contato->aniversario, $m) === 1) {
            return [(int) $m[1], (int) $m[2]];
        }

        $nascimento = $contato->dt_nascimento;

        return $nascimento !== null
            ? [(int) $nascimento->format('j'), (int) $nascimento->format('n')]
            : [null, null];
    }

    /**
     * @param Collection<int,ClientEndereco>|null $enderecos
     */
    private function enderecoPreferido(?Collection $enderecos): ?ClientEndereco
    {
        return $enderecos
            ?->sortBy(fn (ClientEndereco $e): int => self::PRIORIDADE_ENDERECO[$e->tipo] ?? 99)
            ->first();
    }
}
