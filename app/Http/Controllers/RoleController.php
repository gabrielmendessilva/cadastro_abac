<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Lista todos os perfis (roles) com as permissões marcadas.
     */
    public function index()
    {
        // Tela só edita perfis padrão (Root + customizados criados pelo Root via "+ Novo perfil").
        // Os perfis "Personalizado-{nome}" são auto-gerados pela tela de permissões do usuário
        // e não aparecem aqui (gerencie-os entrando no usuário em questão).
        $roles = Role::with('permissions')
            ->where('name', 'not like', 'Personalizado-%')
            ->orderBy('name')
            ->get();
        $permissions = Permission::orderBy('name')->get();

        $grouped = $permissions->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'outros');

        // Lista de perfis personalizados (apenas para visualização — não editável aqui)
        $customRoles = Role::with('permissions', 'users:id,name,email')
            ->where('name', 'like', 'Personalizado-%')
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles'            => $roles,
            'permissionsAll'   => $permissions,
            'permissionGroups' => $grouped,
            'customRoles'      => $customRoles,
        ]);
    }

    /**
     * Cria um novo perfil.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ]);

        Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        return redirect()->route('roles.index')->with('success', 'Perfil criado com sucesso.');
    }

    /**
     * Sincroniza as permissões marcadas para todos os perfis.
     * payload: roles[<role_id>][] = nome_da_permissao
     */
    public function sync(Request $request)
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['array'],
            'roles.*.*' => ['string'],
        ]);

        DB::transaction(function () use ($data) {
            $payload = $data['roles'] ?? [];

            foreach (Role::all() as $role) {
                // Root mantém todas as permissões sempre.
                if ($role->name === 'Root') {
                    $role->syncPermissions(Permission::pluck('name')->all());
                    continue;
                }
                // Perfis "Personalizado-..." não são editados por aqui (são gerenciados pela tela do usuário).
                if (str_starts_with($role->name, 'Personalizado-')) {
                    continue;
                }

                $perms = $payload[$role->id] ?? [];
                $role->syncPermissions($perms);
            }
        });

        return redirect()->route('roles.index')->with('success', 'Permissões atualizadas.');
    }

    /**
     * Remove um perfil. Bloqueado para o perfil Root.
     */
    public function destroy(Role $role)
    {
        if ($role->name === 'Root') {
            return back()->with('error', 'O perfil Root não pode ser removido.');
        }
        if (str_starts_with($role->name, 'Personalizado-')) {
            return back()->with('error', 'Perfis personalizados são removidos automaticamente quando o usuário troca para um perfil padrão.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Perfil removido.');
    }
}
