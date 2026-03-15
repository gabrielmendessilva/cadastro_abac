<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'documents.view', 'documents.create', 'documents.edit', 'documents.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $operator = Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
        $viewer = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);
        $operator->syncPermissions([
            'clients.view', 'clients.create', 'clients.edit',
            'documents.view', 'documents.create', 'documents.edit',
            'users.view',
        ]);
        $viewer->syncPermissions([
            'clients.view',
            'documents.view',
        ]);
    }
}
