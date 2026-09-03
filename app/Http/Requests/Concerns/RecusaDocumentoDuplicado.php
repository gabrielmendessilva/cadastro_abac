<?php

namespace App\Http\Requests\Concerns;

use App\Models\Client;
use Closure;

/**
 * Documento único, mas dizendo de quem é o cadastro que já existe.
 *
 * A mensagem do `unique` só informa que o valor está em uso — não diz onde o
 * registro está. Foi assim que o cadastro de uma mesma pessoa acabou tentado
 * quatro vezes seguidas: o primeiro POST tinha gravado, e a tela de volta não
 * contava isso.
 *
 * O cliente encontrado também vai para o flash `cliente_duplicado`, porque a
 * lista de erros da tela é texto escapado: o link para abrir o cadastro que já
 * existe é montado na view a partir desse flash.
 */
trait RecusaDocumentoDuplicado
{
    /**
     * Regra de documento inédito. `$ignorar` é o cliente sendo editado, que
     * obviamente pode continuar com o próprio documento.
     */
    protected function documentoInedito(?Client $ignorar = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignorar): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $existente = Client::query()
                ->where('document', $value)
                ->when($ignorar, fn ($query) => $query->whereKeyNot($ignorar->getKey()))
                ->first(['id', 'name']);

            if (! $existente) {
                return;
            }

            if ($this->hasSession()) {
                $this->session()->flash('cliente_duplicado', [
                    'id' => $existente->getKey(),
                    'name' => $existente->name,
                ]);
            }

            $fail("Já existe cadastro com este CNPJ / CPF: {$existente->name}.");
        };
    }
}
