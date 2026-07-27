<?php

namespace App\Support;

use App\Models\User;

/**
 * Tela que o usuário vê logo depois de entrar no sistema.
 *
 * O trabalho do dia a dia é sobre as administradoras associadas, então o padrão
 * é a lista de clientes já filtrada em Administradora = Associado (S). Quem não
 * tem permissão de ver clientes cairia em 403 — esse cai no dashboard.
 */
final class TelaInicial
{
    public static function url(?User $user = null): string
    {
        $user ??= auth()->user();

        return $user?->can('clients.view')
            ? route('clients.index', ['associado' => 1])
            : route('dashboard');
    }
}
