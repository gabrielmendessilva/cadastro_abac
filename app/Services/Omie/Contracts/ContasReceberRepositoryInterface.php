<?php

namespace App\Services\Omie\Contracts;

interface ContasReceberRepositoryInterface
{
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function create(array $data): array;

    /** @return array<string,mixed> */
    public function findById(int $omieId): array;

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function update(array $data): array;

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function registerPayment(array $data): array;

    /** @return array<string,mixed> */
    public function delete(int $chaveLancamento): array;
}
