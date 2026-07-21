<?php

namespace Tests\Feature\Rm\Support;

use App\Services\Rm\Contracts\RmReaderInterface;

/**
 * Implementação em memória do RmReaderInterface para os testes:
 * recebe arrays no construtor e reproduz o contrato de agrupamento do reader real.
 */
class FakeRmReader implements RmReaderInterface
{
    /**
     * @param list<array<string,mixed>> $fcfo
     * @param list<array<string,mixed>> $contatos linhas FCFOCONTATO
     * @param list<array<string,mixed>> $defaults linhas FCFODEF
     * @param list<array<string,mixed>> $centrosCusto linhas GCCUSTO
     * @param list<string> $complColumns
     * @param array<string,array<string,mixed>> $compl "coligada|codcfo|idcontato" => linha
     */
    public function __construct(
        private readonly array $fcfo = [],
        private readonly array $contatos = [],
        private readonly array $defaults = [],
        private readonly array $centrosCusto = [],
        private readonly array $complColumns = [],
        private readonly array $compl = [],
    ) {}

    public function preflight(): void
    {
        // no-op: o fake sempre "passa" no preflight
    }

    public function countFcfo(?int $coligada = null): int
    {
        return count($this->filteredFcfo($coligada));
    }

    public function eachFcfoChunk(int $chunkSize, ?int $coligada, ?int $limit, callable $handle): void
    {
        $rows = $this->filteredFcfo($coligada);

        if ($limit !== null) {
            $rows = array_slice($rows, 0, $limit);
        }

        foreach (array_chunk($rows, max(1, $chunkSize)) as $chunk) {
            $handle($chunk);
        }
    }

    public function contatosForKeys(array $codesByColigada): array
    {
        $grouped = [];
        foreach ($this->contatos as $row) {
            $coligada = (int) $row['CODCOLIGADA'];
            $codcfo = trim((string) $row['CODCFO']);
            if (in_array($codcfo, $codesByColigada[$coligada] ?? [], true)) {
                $grouped[$coligada . '|' . $codcfo][] = $row;
            }
        }

        return $grouped;
    }

    public function defaultsForKeys(array $codesByColigada): array
    {
        $grouped = [];
        foreach ($this->defaults as $row) {
            $coligada = (int) $row['CODCOLCFO'];
            $codcfo = trim((string) $row['CODCFO']);
            if (in_array($codcfo, $codesByColigada[$coligada] ?? [], true)) {
                $grouped[$coligada . '|' . $codcfo][] = $row;
            }
        }

        return $grouped;
    }

    public function allCentrosCusto(): array
    {
        return $this->centrosCusto;
    }

    public function contatoComplCustomColumns(): array
    {
        return $this->complColumns;
    }

    public function contatosComplForKeys(array $codesByColigada, array $columns): array
    {
        return $this->compl;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function filteredFcfo(?int $coligada): array
    {
        if ($coligada === null) {
            return $this->fcfo;
        }

        return array_values(array_filter(
            $this->fcfo,
            static fn (array $row): bool => (int) $row['CODCOLIGADA'] === $coligada,
        ));
    }
}
