<?php

namespace App\Services\Omie;

use App\Services\Omie\Contracts\ContasReceberRepositoryInterface;

/**
 * Ponto de entrada para Contas a Receber (Omie).
 *
 * Hoje delega direto pro Repository. O papel do Service é isolar futura
 * orquestração local (gravar registro no DB, disparar evento, retry policy,
 * cache) sem contaminar o Repository, que deve ficar puro de mapeamento
 * ↔ API Omie.
 */
class ContasReceberService
{
    public function __construct(
        private readonly ContasReceberRepositoryInterface $repository,
    ) {}

    public function create(array $data): array
    {
        return $this->repository->create($data);
    }

    public function findById(int $omieId): array
    {
        return $this->repository->findById($omieId);
    }

    public function update(array $data): array
    {
        return $this->repository->update($data);
    }

    public function pay(array $data): array
    {
        return $this->repository->registerPayment($data);
    }

    public function cancel(int $chaveLancamento): array
    {
        return $this->repository->delete($chaveLancamento);
    }
}
