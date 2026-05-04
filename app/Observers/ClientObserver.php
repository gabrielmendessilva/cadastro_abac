<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\ClientAuditLog;

class ClientObserver
{
    private const ABA_POR_CAMPO = [
        // GERAL
        'cod_omie' => 'geral', 'nome_fantasia' => 'geral', 'nome' => 'geral', 'nome_comercial' => 'geral',
        'possui_outro_nome' => 'geral', 'outros_nomes' => 'geral',
        'classificacao' => 'geral', 'categoria' => 'geral', 'cpf_cnpj' => 'geral', 'cpf' => 'geral',
        'rg' => 'geral', 'dt_nascimento' => 'geral', 'regional' => 'geral',
        'inscri_estadual' => 'geral', 'inscri_municipal' => 'geral', 'tipo_cliente' => 'geral',
        'autenticacao_whatsapp' => 'geral', 'status' => 'geral', 'status_empresa' => 'geral',
        'associado_abac' => 'geral', 'dt_filiacao_abac' => 'geral', 'num_filiacao_abac' => 'geral',
        'dt_desfiliacao_abac' => 'geral', 'motivo_desfiliacao_abac' => 'geral', 'obs_abac' => 'geral',
        'associado_sinac' => 'geral', 'dt_filiacao_sinac' => 'geral', 'num_filiacao_sinac' => 'geral',
        'dt_desfiliacao_sinac' => 'geral', 'motivo_desfiliacao_sinac' => 'geral', 'obs_sinac' => 'geral',
        'dt_abertura_empresa' => 'geral', 'dt_aniversario_empresa' => 'geral',
        'dt_autorizacao_consorcio' => 'geral', 'dt_pedido_consorcio' => 'geral', 'dt_bacen' => 'geral',
        'situacao_abac' => 'geral', 'classificao_administradora' => 'geral', 'associado' => 'geral',
        'responsavel_empresa' => 'geral', 'email_admin' => 'geral', 'contato_name_admin' => 'geral',
        'email_conac' => 'geral', 'celular_admin' => 'geral', 'telefone' => 'geral',
        'email_ouvidoria' => 'geral', 'telefone_ouvidoria' => 'geral',
        'segmentos' => 'geral', 'area_atuacao' => 'geral',
        'email_2' => 'geral', 'email_3' => 'geral', 'email_4' => 'geral',
        'email_5' => 'geral', 'email_6' => 'geral', 'email_7' => 'geral',
        'obs' => 'geral', 'obs_2' => 'geral',
        // FINANCEIRO
        'emails_boletos' => 'financeiro', 'possui_contrato_ativo' => 'financeiro',
        // SECRETARIA
        'presidente_atual' => 'secretaria', 'mandato_inicio' => 'secretaria',
        'mandato_termino' => 'secretaria', 'mandato_alerta' => 'secretaria',
        'email_presidente' => 'secretaria', 'email_secretaria' => 'secretaria',
        // CADASTRO
        'segmento' => 'cadastro', 'obs_cadastro' => 'cadastro',
        // JURÍDICO
        'obs_juridico' => 'juridico', 'obs_sinac_juridico' => 'juridico',
    ];

    public function created(Client $client): void
    {
        ClientAuditLog::create([
            'client_id' => $client->id,
            'user_id' => auth()->id(),
            'aba' => 'geral',
            'campo' => null,
            'valor_anterior' => null,
            'valor_novo' => null,
            'acao' => 'created',
            'created_at' => now(),
        ]);
    }

    public function updated(Client $client): void
    {
        $userId = auth()->id();
        $changes = $client->getChanges();
        $original = $client->getOriginal();

        foreach ($changes as $campo => $novo) {
            if (in_array($campo, ['updated_at', 'updated_by'], true)) {
                continue;
            }

            ClientAuditLog::create([
                'client_id' => $client->id,
                'user_id' => $userId,
                'aba' => self::ABA_POR_CAMPO[$campo] ?? 'outros',
                'campo' => $campo,
                'valor_anterior' => is_scalar($original[$campo] ?? null) ? (string) $original[$campo] : json_encode($original[$campo] ?? null),
                'valor_novo' => is_scalar($novo) ? (string) $novo : json_encode($novo),
                'acao' => 'update',
                'created_at' => now(),
            ]);
        }
    }
}
