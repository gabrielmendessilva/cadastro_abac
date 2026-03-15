<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientEndereco extends Model
{
    protected $table = 'client_enderecos';

    protected $fillable = [
        'client_id',
        'cep',
        'rua',
        'numero',
        'complemento',
        'bairro',
        'pais',
        'estado',
        'cod_ibge',
        'municipio',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
