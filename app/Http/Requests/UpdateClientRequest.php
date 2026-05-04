<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identificação
            'cod_omie' => ['nullable', 'string', 'max:50'],
            'nome' => ['nullable', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'nome_comercial' => ['nullable', 'string', 'max:255'],
            'possui_outro_nome' => ['nullable', 'boolean'],
            'outros_nomes' => ['nullable', 'string'],
            'classificacao' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'rg' => ['nullable', 'string', 'max:30'],
            'dt_nascimento' => ['nullable', 'date'],
            'regional' => ['nullable', 'string', 'max:100'],
            'inscri_estadual' => ['nullable', 'string', 'max:50'],
            'inscri_municipal' => ['nullable', 'string', 'max:50'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'autenticacao_whatsapp' => ['nullable', 'boolean'],

            // ABAC
            'associado_abac' => ['nullable', 'boolean'],
            'dt_filiacao_abac' => ['nullable', 'date'],
            'num_filiacao_abac' => ['nullable', 'string', 'max:50'],
            'dt_desfiliacao_abac' => ['nullable', 'date'],
            'motivo_desfiliacao_abac' => ['nullable', 'string', 'max:500'],
            'obs_abac' => ['nullable', 'string'],

            // SINAC
            'associado_sinac' => ['nullable', 'boolean'],
            'dt_filiacao_sinac' => ['nullable', 'date'],
            'num_filiacao_sinac' => ['nullable', 'string', 'max:50'],
            'dt_desfiliacao_sinac' => ['nullable', 'date'],
            'motivo_desfiliacao_sinac' => ['nullable', 'string', 'max:500'],
            'obs_sinac' => ['nullable', 'string'],

            // Datas / status
            'dt_abertura_empresa' => ['nullable', 'date'],
            'dt_aniversario_empresa' => ['nullable', 'date'],
            'dt_autorizacao_consorcio' => ['nullable', 'date'],
            'dt_pedido_consorcio' => ['nullable', 'date'],
            'dt_bacen' => ['nullable', 'date'],
            'status_empresa' => ['nullable', 'string', 'max:50'],
            'situacao_abac' => ['nullable', 'string', 'max:100'],
            'classificao_administradora' => ['nullable', 'string', 'max:100'],
            'associado' => ['nullable', 'string', 'max:50'],

            // Contatos
            'responsavel_empresa' => ['nullable', 'string', 'max:255'],
            'email_admin' => ['nullable', 'string', 'max:255'],
            'contato_name_admin' => ['nullable', 'string', 'max:255'],
            'email_conac' => ['nullable', 'string', 'max:255'],
            'celular_admin' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email_ouvidoria' => ['nullable', 'string', 'max:255'],
            'telefone_ouvidoria' => ['nullable', 'string', 'max:30'],

            'segmentos' => ['nullable', 'string'],
            'area_atuacao' => ['nullable', 'string'],
            'email_2' => ['nullable', 'string', 'max:255'],
            'email_3' => ['nullable', 'string', 'max:255'],
            'email_4' => ['nullable', 'string', 'max:255'],
            'email_5' => ['nullable', 'string', 'max:255'],
            'email_6' => ['nullable', 'string', 'max:255'],
            'email_7' => ['nullable', 'string', 'max:255'],

            // FINANCEIRO
            'emails_boletos' => ['nullable', 'string'],
            'possui_contrato_ativo' => ['nullable', 'boolean'],

            // SECRETARIA
            'presidente_atual' => ['nullable', 'string', 'max:255'],
            'mandato_inicio' => ['nullable', 'date'],
            'mandato_termino' => ['nullable', 'date'],
            'mandato_alerta' => ['nullable', 'boolean'],
            'email_presidente' => ['nullable', 'string', 'max:255'],
            'email_secretaria' => ['nullable', 'string', 'max:255'],

            // CADASTRO
            'segmento' => ['nullable', 'string', 'max:100'],
            'obs_cadastro' => ['nullable', 'string'],

            // JURÍDICO
            'obs_juridico' => ['nullable', 'string'],
            'obs_sinac_juridico' => ['nullable', 'string'],

            'obs' => ['nullable', 'string'],
            'obs_2' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $bools = [
            'possui_outro_nome', 'autenticacao_whatsapp', 'associado_abac', 'associado_sinac',
            'possui_contrato_ativo', 'mandato_alerta', 'status',
        ];
        $merge = [];
        foreach ($bools as $key) {
            if ($this->has($key)) {
                $merge[$key] = $this->boolean($key);
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
    }
}
