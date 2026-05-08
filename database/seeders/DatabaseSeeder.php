<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(ListasDominioSeeder::class);
        $this->call(ComitesSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@sistema.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'status' => true,
            ]
        );

        $admin->syncRoles(['Administrador']);
    }
}
