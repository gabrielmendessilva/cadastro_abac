<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFiliacaoHistorico extends Model
{
    protected $table = 'client_filiacoes_historico';

    protected $fillable = [
        'client_id', 'tipo', 'num_filiacao', 'dt_filiacao', 'dt_desfiliacao',
        'motivo_desfiliacao', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'dt_filiacao' => 'date',
            'dt_desfiliacao' => 'date',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
