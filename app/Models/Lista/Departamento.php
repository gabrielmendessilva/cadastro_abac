<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos_lista';
    protected $fillable = ['nome', 'descricao', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
