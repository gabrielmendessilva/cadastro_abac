<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Funcao extends Model
{
    protected $table = 'funcoes';
    protected $fillable = ['nome', 'descricao', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
