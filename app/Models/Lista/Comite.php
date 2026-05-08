<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Comite extends Model
{
    protected $table = 'comites';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
    ];
}
