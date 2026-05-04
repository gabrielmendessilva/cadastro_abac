<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'nome_comercial' => fn() => $table->string('nome_comercial', 255)->nullable(),
                'possui_outro_nome' => fn() => $table->boolean('possui_outro_nome')->default(false),
                'outros_nomes' => fn() => $table->text('outros_nomes')->nullable(),
                'cpf' => fn() => $table->string('cpf', 20)->nullable(),
                'rg' => fn() => $table->string('rg', 30)->nullable(),
                'dt_nascimento' => fn() => $table->date('dt_nascimento')->nullable(),
                'autenticacao_whatsapp' => fn() => $table->boolean('autenticacao_whatsapp')->default(false),

                // Filiação ABAC atual
                'associado_abac' => fn() => $table->boolean('associado_abac')->default(false),
                'dt_filiacao_abac' => fn() => $table->date('dt_filiacao_abac')->nullable(),
                'num_filiacao_abac' => fn() => $table->string('num_filiacao_abac', 50)->nullable(),
                'dt_desfiliacao_abac' => fn() => $table->date('dt_desfiliacao_abac')->nullable(),
                'motivo_desfiliacao_abac' => fn() => $table->string('motivo_desfiliacao_abac', 500)->nullable(),
                'obs_abac' => fn() => $table->text('obs_abac')->nullable(),

                // Filiação SINAC atual
                'associado_sinac' => fn() => $table->boolean('associado_sinac')->default(false),
                'dt_filiacao_sinac' => fn() => $table->date('dt_filiacao_sinac')->nullable(),
                'num_filiacao_sinac' => fn() => $table->string('num_filiacao_sinac', 50)->nullable(),
                'dt_desfiliacao_sinac' => fn() => $table->date('dt_desfiliacao_sinac')->nullable(),
                'motivo_desfiliacao_sinac' => fn() => $table->string('motivo_desfiliacao_sinac', 500)->nullable(),
                'obs_sinac' => fn() => $table->text('obs_sinac')->nullable(),

                // Datas / status da empresa
                'dt_abertura_empresa' => fn() => $table->date('dt_abertura_empresa')->nullable(),
                'dt_aniversario_empresa' => fn() => $table->date('dt_aniversario_empresa')->nullable(),
                'dt_autorizacao_consorcio' => fn() => $table->date('dt_autorizacao_consorcio')->nullable(),
                'dt_pedido_consorcio' => fn() => $table->date('dt_pedido_consorcio')->nullable(),
                'status_empresa' => fn() => $table->string('status_empresa', 50)->nullable(),

                // Contatos
                'responsavel_empresa' => fn() => $table->string('responsavel_empresa', 255)->nullable(),
                'email_ouvidoria' => fn() => $table->string('email_ouvidoria', 255)->nullable(),
                'telefone_ouvidoria' => fn() => $table->string('telefone_ouvidoria', 30)->nullable(),

                // FINANCEIRO
                'emails_boletos' => fn() => $table->text('emails_boletos')->nullable(),
                'possui_contrato_ativo' => fn() => $table->boolean('possui_contrato_ativo')->default(false),

                // SECRETARIA
                'presidente_atual' => fn() => $table->string('presidente_atual', 255)->nullable(),
                'mandato_inicio' => fn() => $table->date('mandato_inicio')->nullable(),
                'mandato_termino' => fn() => $table->date('mandato_termino')->nullable(),
                'mandato_alerta' => fn() => $table->boolean('mandato_alerta')->default(true),
                'email_presidente' => fn() => $table->string('email_presidente', 255)->nullable(),
                'email_secretaria' => fn() => $table->string('email_secretaria', 255)->nullable(),

                // CADASTRO
                'segmento' => fn() => $table->string('segmento', 100)->nullable(),
                'obs_cadastro' => fn() => $table->text('obs_cadastro')->nullable(),

                // JURÍDICO
                'obs_juridico' => fn() => $table->text('obs_juridico')->nullable(),
                'obs_sinac_juridico' => fn() => $table->text('obs_sinac_juridico')->nullable(),

                // Auditoria simples
                'created_by' => fn() => $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(),
                'updated_by' => fn() => $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(),
            ];

            foreach ($columns as $name => $maker) {
                if (!Schema::hasColumn('clients', $name)) {
                    $maker();
                }
            }
        });
    }

    public function down(): void
    {
        $cols = [
            'nome_comercial', 'possui_outro_nome', 'outros_nomes', 'cpf', 'rg', 'dt_nascimento',
            'autenticacao_whatsapp',
            'associado_abac', 'dt_filiacao_abac', 'num_filiacao_abac', 'dt_desfiliacao_abac',
            'motivo_desfiliacao_abac', 'obs_abac',
            'associado_sinac', 'dt_filiacao_sinac', 'num_filiacao_sinac', 'dt_desfiliacao_sinac',
            'motivo_desfiliacao_sinac', 'obs_sinac',
            'dt_abertura_empresa', 'dt_aniversario_empresa', 'dt_autorizacao_consorcio', 'dt_pedido_consorcio',
            'status_empresa',
            'responsavel_empresa', 'email_ouvidoria', 'telefone_ouvidoria',
            'emails_boletos', 'possui_contrato_ativo',
            'presidente_atual', 'mandato_inicio', 'mandato_termino', 'mandato_alerta',
            'email_presidente', 'email_secretaria',
            'segmento', 'obs_cadastro',
            'obs_juridico', 'obs_sinac_juridico',
            'created_by', 'updated_by',
        ];

        Schema::table('clients', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('clients', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
