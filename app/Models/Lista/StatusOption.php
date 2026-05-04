<?php

namespace App\Models\Lista;

use Illuminate\Database\Eloquent\Model;

class StatusOption extends Model
{
    protected $table = 'status_options';
    protected $fillable = ['nome', 'descricao', 'ativo'];
    protected function casts(): array { return ['ativo' => 'boolean']; }
}
