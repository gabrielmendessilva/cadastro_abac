<?php

namespace App\Services\Omie\Contracts;

interface OmieClientInterface
{
    /**
     * Envelopa e dispara uma chamada à API Omie.
     *
     * @param  string  $endpoint  URI relativa à base (ex.: "v1/financas/contareceber/")
     * @param  string  $call      Ação Omie (ex.: "IncluirContaReceber")
     * @param  array<string,mixed>  $param  Conteúdo de param[0] (sem envelope de app_key/secret/call)
     * @return array<string,mixed>          Resposta JSON decodificada
     *
     * @throws \App\Services\Omie\Exceptions\OmieException Em erro HTTP ou faultstring lógico da Omie
     */
    public function request(string $endpoint, string $call, array $param): array;
}
