<?php

namespace App\Http\Requests\Omie;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de recebimento (LancarRecebimento).
 *
 * `data_recebimento` é o nome semanticamente correto. O projeto de origem usava
 * `vencimento` no mesmo campo — aceitamos como fallback (@deprecated) para não
 * quebrar integrações que já batem com o nome antigo.
 */
class PayContaReceberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_lancamento' => ['required', 'integer'],
            'cc_id'             => ['required', 'integer'],
            'valor'             => ['required', 'numeric', 'min:0.01'],
            'data_recebimento'  => ['required_without:vencimento', 'nullable', 'date_format:d/m/Y'],
            'vencimento'        => ['required_without:data_recebimento', 'nullable', 'date_format:d/m/Y'], // @deprecated fallback
        ];
    }
}
