<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Regional extends Model
{
    protected $table = 'regionais';
    protected $fillable = ['nome', 'descricao', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
