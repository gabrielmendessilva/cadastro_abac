<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientComite extends Model
{
    protected $table = 'client_comites';

    protected $fillable = ['client_id', 'contato_id', 'comite_nome', 'papel', 'observacoes'];

    public const PAPEIS = [
        'coordenador' => 'Coordenador',
        'titular' => 'Titular',
        'suplente' => 'Suplente',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contato()
    {
        return $this->belongsTo(ClientContato::class, 'contato_id');
    }
}
