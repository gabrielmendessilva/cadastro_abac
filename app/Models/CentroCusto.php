<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Centro de custo do cliente, importado do TOTVS RM pelo rm:import.
 * Tabela satélite de clients (vínculo por client_id).
 */
class CentroCusto extends Model
{
    protected $table = 'centros_custo';

    protected $fillable = [
        'client_id',
        'codigo',
        'nome',
        'codigo_reduzido',
        'classificacao',
        'ativo',
        'permite_lancamentos',
        'responsavel',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'permite_lancamentos' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
