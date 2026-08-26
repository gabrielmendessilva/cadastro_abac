<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * Usuário Root pode tudo (bypass via Gate::before no AppServiceProvider).
     */
    public function isRoot(): bool
    {
        return $this->hasRole('Root');
    }

    /**
     * Quem administra perfis e permissões: a tela de Perfis & Permissões e as
     * permissões individuais de cada usuário.
     *
     * O Administrador entra aqui junto com o Root — é o perfil de quem cuida do
     * sistema no dia a dia. Root continua sendo o teto: nem Administrador nem
     * ninguém consegue atribuir o perfil Root ou mexer num usuário Root
     * (UserController) e o perfil Root nunca perde permissões (RoleController).
     */
    public function podeGerenciarPermissoes(): bool
    {
        return $this->isRoot() || $this->hasRole('Administrador');
    }
}
