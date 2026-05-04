<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class Segmento extends Model
{
    protected $table = 'segmentos';
    protected $fillable = ['nome', 'descricao', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
