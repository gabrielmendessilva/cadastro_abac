<?php

namespace App\Http\Requests\Omie;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NB: os endpoints /api/omie/* foram migrados sem auth por paridade com o projeto de origem
 * (abac_admin não protegia essas rotas). Débito técnico documentado no plano de migração.
 * Se colocar sob middleware('auth'), trocar authorize() por check de permissão spatie.
 */
class CreateContaReceberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo_lancamento_integracao' => ['required', 'string', 'max:60'],
            'codRm'                        => ['required', 'integer'],
            'vencimento'                   => ['required', 'date_format:d/m/Y'],
            'valor'                        => ['required', 'numeric', 'min:0'],
            'cc_id'                        => ['required', 'integer'],
            'user_id'                      => ['required'],
            'categoria'                    => ['required', 'string'],
            'tipo_receber'                 => ['required', 'string', 'max:60'],
            'projeto'                      => ['nullable', 'integer'],
            'observacao'                   => ['nullable', 'string', 'max:500'],
            'idCompra'                     => ['nullable', 'string', 'max:60'],
            'numero_parcela'               => ['nullable', 'string', 'max:10'],
            'desconto'                     => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
