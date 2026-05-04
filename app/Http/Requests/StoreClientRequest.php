<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'cod_omie' => ['nullable', 'string', 'max:50'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'regional' => ['nullable', 'string', 'max:100'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
