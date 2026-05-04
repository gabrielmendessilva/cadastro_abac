<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'type',
        'category',
        'file_path',
        'original_name',
        'description',
        'expiration_date',
        'status',
        'uploaded_by',
    ];

    public const CATEGORIES = [
        'filiacao_abac' => 'Documentos de filiação ABAC',
        'filiacao_sinac' => 'Documentos de filiação SINAC',
        'bcb' => 'Documentos BCB',
        'demais' => 'Demais documentos',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'expiration_date' => 'date',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
}
