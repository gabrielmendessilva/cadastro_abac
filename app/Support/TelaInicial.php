<?php

namespace App\Support;

use App\Models\User;

/**
 * Tela que o usuário vê logo depois de entrar no sistema.
 *
 * O trabalho do dia a dia é sobre as administradoras associadas, então o padrão
 * é a lista de clientes já filtrada em Administradora = Associado (S). Quem não
 * tem permissão de ver clientes cairia em 403 — esse cai no dashboard.
 *
 * O Administrador é a exceção: entra direto em Perfis & Permissões, que é o
 * trabalho dele. O Root não — ele usa o sistema inteiro e segue no fluxo normal.
 */
final class TelaInicial
{
    public static function url(?User $user = null): string
    {
        $user ??= auth()->user();

        // Primeiro acesso: nada de sistema antes de trocar a senha temporária
        // que veio por e-mail (middleware 'senha.trocada' segura o resto).
        if ($user?->must_change_password) {
            return route('password.change');
        }

        // Só o perfil Administrador, não todo mundo que administra permissões —
        // caso contrário o Root também cairia aqui.
        if ($user?->hasRole('Administrador')) {
            return route('roles.index');
        }

        return $user?->can('clients.view')
            ? route('clients.index', ['associado' => 1])
            : route('dashboard');
    }
}
