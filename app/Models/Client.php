<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'possui_outro_nome' => 'boolean',
            'autenticacao_whatsapp' => 'boolean',
            'associado_abac' => 'boolean',
            'associado_sinac' => 'boolean',
            'possui_contrato_ativo' => 'boolean',
            'mandato_alerta' => 'boolean',
            'dt_nascimento' => 'date',
            'dt_filiacao_abac' => 'date',
            'dt_desfiliacao_abac' => 'date',
            'dt_filiacao_sinac' => 'date',
            'dt_desfiliacao_sinac' => 'date',
            'dt_abertura_empresa' => 'date',
            'dt_aniversario_empresa' => 'date',
            'dt_autorizacao_consorcio' => 'date',
            'dt_pedido_consorcio' => 'date',
            'dt_bacen' => 'date',
            'mandato_inicio' => 'date',
            'mandato_termino' => 'date',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contatos()
    {
        return $this->hasMany(ClientContato::class, 'client_id');
    }

    public function opcionais()
    {
        return $this->hasMany(ClientOpcional::class, 'client_id');
    }

    public function enderecos()
    {
        return $this->hasMany(ClientEndereco::class, 'client_id');
    }

    public function filiacoesHistorico()
    {
        return $this->hasMany(ClientFiliacaoHistorico::class);
    }

    public function redesSociais()
    {
        return $this->hasMany(ClientRedeSocial::class);
    }

    public function contratos()
    {
        return $this->hasMany(ClientContrato::class);
    }

    public function socios()
    {
        return $this->hasMany(ClientSocio::class);
    }

    public function juridicoContatos()
    {
        return $this->hasMany(ClientJuridicoContato::class);
    }

    public function comites()
    {
        return $this->hasMany(ClientComite::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'client_tag');
    }

    public function auditLogs()
    {
        return $this->hasMany(ClientAuditLog::class);
    }
}
