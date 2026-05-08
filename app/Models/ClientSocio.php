<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSocio extends Model
{
    protected $table = 'client_socios';

    protected $fillable = [
        'client_id', 'papel', 'nome', 'cpf_cnpj',
        'email', 'telefone', 'quota_participacao',
        'mandato_inicio', 'mandato_termino',
        'observacoes',
    ];

    protected $casts = [
        'mandato_inicio' => 'date',
        'mandato_termino' => 'date',
        'quota_participacao' => 'decimal:4',
    ];

    public const PAPEIS = [
        'socio' => 'Sócio',
        'administrador' => 'Administrador',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
