<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientOpcional extends Model
{
    protected $table = 'client_opcionais';

    protected $fillable = [
        'client_id',
        'site',
        'inicio_atv',
        'num_abac',
        'dt_f_abac',
        'num_sinac',
        'dt_f_sinac',
    ];

    protected $casts = [
        'inicio_atv' => 'date',
        'dt_f_abac' => 'date',
        'dt_f_sinac' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
