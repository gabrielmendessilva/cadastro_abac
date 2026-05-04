<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientContrato extends Model
{
    protected $table = 'client_contratos';

    protected $fillable = ['client_id', 'descricao', 'responsavel', 'dt_vencimento', 'ativo', 'observacoes'];

    protected function casts(): array
    {
        return [
            'dt_vencimento' => 'date',
            'ativo' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
