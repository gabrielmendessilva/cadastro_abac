<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['nome', 'cor'];

    public const CORES_DISPONIVEIS = [
        'slate' => 'Cinza',
        'blue' => 'Azul',
        'emerald' => 'Verde',
        'amber' => 'Amarelo',
        'rose' => 'Vermelho',
        'purple' => 'Roxo',
        'cyan' => 'Ciano',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_tag');
    }
}
