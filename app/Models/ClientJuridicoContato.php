<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientJuridicoContato extends Model
{
    protected $table = 'client_juridico_contatos';

    protected $fillable = ['client_id', 'area', 'nome', 'funcao', 'departamento', 'email', 'telefone'];

    public const AREAS = [
        'juridico' => 'Jurídico',
        'sinac' => 'SINAC',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
