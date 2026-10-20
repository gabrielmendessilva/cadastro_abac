<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissões da tela de Relatórios: `relatorios.view` abre a tela e mostra a
 * prévia; `relatorios.export` baixa o Excel/PDF. Separadas porque baixar a base
 * filtrada é tirar dado do sistema — dá para liberar a consulta sem o arquivo.
 *
 * Root e Administrador recebem as duas; os demais perfis ficam a cargo de
 * quem administra em "Perfis & Permissões".
 *
 * Migration e não só seeder: o deploy roda `migrate --force` em produção, mas
 * não o RolePermissionSeeder. Idempotente (firstOrCreate + guard de tabela).
 */
return new class extends Migration
{
    private const PERMISSOES = ['relatorios.view', 'relatorios.export'];

    private const PERFIS = ['Root', 'Administrador'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permissionModel = app(PermissionRegistrar::class)->getPermissionClass();
        $roleModel = app(PermissionRegistrar::class)->getRoleClass();

        foreach (self::PERMISSOES as $nome) {
            $permissionModel::firstOrCreate(['name' => $nome, 'guard_name' => 'web']);
        }

        foreach ($roleModel::whereIn('name', self::PERFIS)->where('guard_name', 'web')->get() as $perfil) {
            $perfil->givePermissionTo(self::PERMISSOES);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->whereIn('name', self::PERMISSOES)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
