<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientSocio extends Model
{
    protected $table = 'client_socios';

    protected $fillable = ['client_id', 'papel', 'nome', 'cpf_cnpj', 'observacoes'];

    public const PAPEIS = [
        'socio' => 'Sócio',
        'administrador' => 'Administrador',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
