<?php

namespace App\Http\Requests\Concerns;

use App\Services\Rm\Support\Normalizer;

/**
 * Põe o CPF/CNPJ digitado no formato canônico do banco antes de validar.
 *
 * `clients.document` guarda o documento formatado (51.855.716/0001-01). Sem esta
 * normalização, digitar 51855716000101 passa pela regra `unique` mesmo já
 * existindo o mesmo documento mascarado — criando um duplicado que quebra tanto
 * a busca por CNPJ quanto o dedup das importações.
 *
 * Entradas com contagem de dígitos inválida passam intactas, para a mensagem de
 * erro sair da validação e não de uma máscara aplicada a lixo.
 */
trait NormalizaDocumento
{
    protected function normalizarDocumento(): void
    {
        if (! $this->has('document')) {
            return;
        }

        $digitos = Normalizer::digits((string) $this->input('document'));

        if (in_array(strlen($digitos), [11, 14], true)) {
            $this->merge(['document' => Normalizer::formatCpfCnpj($digitos)]);
        }
    }
}
