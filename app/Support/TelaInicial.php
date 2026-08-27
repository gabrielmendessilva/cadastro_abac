<?php

namespace App\Support;

use App\Models\User;

/**
 * Tela que o usuário vê logo depois de entrar no sistema.
 *
 * O trabalho do dia a dia é sobre as administradoras associadas, então todo
 * mundo cai na lista de clientes, sem exceção por perfil. Ela já vem filtrada em
 * Administradora = Associado (S) e Status = Ativo — esse padrão mora em
 * ClientController::filtroComPadrao e vale para /clients sem parâmetro, por isso
 * aqui a rota vai limpa: o filtro tem um dono só.
 *
 * Quem não tem permissão de ver clientes cairia em 403 — esse cai no dashboard.
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

        return $user?->can('clients.view')
            ? route('clients.index')
            : route('dashboard');
    }
}
