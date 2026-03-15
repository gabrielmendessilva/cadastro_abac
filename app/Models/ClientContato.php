<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContato extends Model
{
    protected $table = 'client_contatos';

    protected $fillable = [
        'client_id',
        'user_id',
        'nome',
        'funcao',
        'dt_nascimento',
        'email',
        'telefone',
        'telefone_2',
        'obs',
        'departamento',
        'outro_departamento',
        'representante_legal',
        'comite',
        'unlock_whatsApp',
    ];

    protected $casts = [
        'dt_nascimento' => 'date',
        'representante_legal' => 'boolean',
        'comite' => 'boolean',
        'unlock_whatsApp' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
