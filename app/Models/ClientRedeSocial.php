<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientRedeSocial extends Model
{
    protected $table = 'client_redes_sociais';

    protected $fillable = ['client_id', 'tipo', 'rotulo', 'url'];

    public const TIPOS = [
        'site' => 'Site',
        'linkedin' => 'LinkedIn',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'outros' => 'Outros',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
