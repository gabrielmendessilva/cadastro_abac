<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_id', 'user_id', 'aba', 'campo',
        'valor_anterior', 'valor_novo', 'acao', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
