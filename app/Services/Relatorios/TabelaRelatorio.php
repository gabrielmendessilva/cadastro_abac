<?php

namespace App\Services\Relatorios;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Base dos relatórios v2: exportam a tabela inteira, sem filtro, com uma
 * coluna por campo do banco. As colunas saem do schema, então campo novo na
 * tabela entra no relatório sem mexer aqui.
 */
abstract class TabelaRelatorio implements Planilha
{
    private readonly string $geradoEm;

    /** @var Collection<int,array<string,mixed>>|null */
    private ?Collection $linhas = null;

    private ?array $colunas = null;

    public function __construct(private readonly string $geradoPor)
    {
        $this->geradoEm = now()->format('d/m/Y H:i');
    }

    /**
     * Colunas na ordem da planilha, sem a largura (calculada pelo conteúdo).
     *
     * @return array<string,array{titulo:string,tipo?:string,centro?:bool}>
     */
    abstract protected function definicoes(): array;

    /** @return Collection<int,array<string,mixed>> */
    abstract protected function montaLinhas(): Collection;

    /** Prefixo do nome do arquivo baixado, ex. "relatorio-clientes". */
    abstract protected function prefixoDoArquivo(): string;

    public function geradoEm(): string
    {
        return $this->geradoEm;
    }

    public function geradoPor(): string
    {
        return $this->geradoPor;
    }

    /** @return Collection<int,array<string,mixed>> */
    public function linhas(): Collection
    {
        return $this->linhas ??= $this->montaLinhas();
    }

    public function colunas(): array
    {
        if ($this->colunas !== null) {
            return $this->colunas;
        }

        $colunas = [];
        foreach ($this->definicoes() as $chave => $definicao) {
            $colunas[$chave] = $definicao + ['largura' => $this->largura($chave, $definicao['titulo'])];
        }

        return $this->colunas = $colunas;
    }

    public function nomeDoArquivo(string $extensao): string
    {
        return $this->prefixoDoArquivo().'-'.now()->format('Y-m-d-His').'.'.$extensao;
    }

    /**
     * Uma definição por coluna da tabela, na ordem do banco: rótulo do mapa (ou
     * o nome do campo, legível) e tipo pelo cast do model — data vira data de
     * verdade no Excel, o resto vai como texto para não perder zero à esquerda.
     *
     * @param  array<string,string>  $rotulos
     * @param  list<string>  $ignoradas
     * @return array<string,array{titulo:string,tipo?:string,centro?:bool}>
     */
    protected static function colunasDaTabela(Model $model, array $rotulos, array $ignoradas = [], string $prefixo = ''): array
    {
        $definicoes = [];

        foreach (Schema::getColumnListing($model->getTable()) as $coluna) {
            if (in_array($coluna, $ignoradas, true)) {
                continue;
            }

            $definicao = ['titulo' => $rotulos[$coluna] ?? Str::of($coluna)->replace('_', ' ')->ucfirst()->toString()];

            if (in_array($coluna, ['created_at', 'updated_at'], true)) {
                $definicao += ['tipo' => 'datahora', 'centro' => true];
            } elseif ($model->hasCast($coluna, ['date', 'datetime', 'immutable_date', 'immutable_datetime'])) {
                $definicao += ['tipo' => 'data', 'centro' => true];
            } elseif ($model->hasCast($coluna, ['bool', 'boolean'])) {
                $definicao += ['centro' => true];
            } elseif ($coluna !== 'id') {
                $definicao += ['tipo' => 'texto'];
            }

            $definicoes[$prefixo.$coluna] = $definicao;
        }

        return $definicoes;
    }

    /**
     * Valor do campo pronto para a célula: boolean vira Sim/Não. Data inválida
     * que veio do legado não derruba o relatório — sai o texto cru.
     */
    protected static function valor(Model $model, string $coluna): mixed
    {
        try {
            $valor = $model->getAttribute($coluna);
        } catch (Throwable) {
            return $model->getRawOriginal($coluna);
        }

        if ($valor !== null && $model->hasCast($coluna, ['bool', 'boolean'])) {
            return $valor ? 'Sim' : 'Não';
        }

        return $valor;
    }

    /** Largura pelo maior conteúdo da coluna, com teto para texto longo. */
    private function largura(string $chave, string $titulo): int
    {
        $maior = mb_strlen($titulo);

        foreach ($this->linhas() as $linha) {
            $valor = $linha[$chave] ?? null;
            $tamanho = $valor instanceof CarbonInterface ? 16 : mb_strlen((string) $valor);
            $maior = max($maior, $tamanho);
        }

        return min(50, max(8, $maior + 2));
    }
}
