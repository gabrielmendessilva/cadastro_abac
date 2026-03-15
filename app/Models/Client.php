<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'fantasy_name',
        'document',
        'email',
        'phone',
        'mobile',
        'zipcode',
        'address',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contatos()
    {
        return $this->hasMany(ClientContato::class, 'client_id');
    }

    public function opcionais()
    {
        return $this->hasMany(ClientOpcional::class, 'client_id');
    }
    
    public function enderecos()
    {
        return $this->hasMany(ClientEndereco::class, 'client_id');
    }
}
