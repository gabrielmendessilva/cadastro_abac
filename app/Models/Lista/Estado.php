<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'estados';
    protected $fillable = ['uf', 'nome', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
