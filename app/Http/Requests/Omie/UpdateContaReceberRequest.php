<?php

namespace App\Http\Requests\Omie;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Atualização de conta a receber já registrada na Omie. Espera o mesmo payload do create,
 * mais o identificador Omie (codigo_lancamento_omie ou codigo_lancamento_integracao).
 */
class UpdateContaReceberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_lancamento_omie'       => ['required_without:codigo_lancamento_integracao', 'integer'],
            'codigo_lancamento_integracao' => ['required_without:codigo_lancamento_omie', 'string', 'max:60'],
            'codRm'                        => ['sometimes', 'required', 'integer'],
            'vencimento'                   => ['sometimes', 'required', 'date_format:d/m/Y'],
            'valor'                        => ['sometimes', 'required', 'numeric', 'min:0'],
            'cc_id'                        => ['sometimes', 'required', 'integer'],
            'user_id'                      => ['sometimes', 'required'],
            'categoria'                    => ['sometimes', 'required', 'string'],
            'tipo_receber'                 => ['sometimes', 'required', 'string', 'max:60'],
            'projeto'                      => ['nullable', 'integer'],
            'observacao'                   => ['nullable', 'string', 'max:500'],
            'idCompra'                     => ['nullable', 'string', 'max:60'],
            'numero_parcela'               => ['nullable', 'string', 'max:10'],
            'desconto'                     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
