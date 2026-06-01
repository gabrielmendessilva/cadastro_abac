<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('users.view'), 403);

        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('users.create'), 403);

        $roles = Role::orderBy('name')
            ->when(!auth()->user()->isRoot(), fn ($q) => $q->where('name', '!=', 'Root'))
            ->get();
        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        abort_unless(auth()->user()->can('users.create'), 403);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->boolean('status', true),
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'Usuário cadastrado com sucesso.');
    }

    public function show(User $user)
    {
        abort_unless(auth()->user()->can('users.view'), 403);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

        // Bloqueio: não-Root não pode editar Root
        if ($user->hasRole('Root') && !auth()->user()->isRoot()) {
            abort(403, 'Apenas usuários Root podem editar outros usuários Root.');
        }

        $roles = Role::orderBy('name')
            ->when(!auth()->user()->isRoot(), fn ($q) => $q->where('name', '!=', 'Root'))
            ->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

        // Apenas Root pode editar outro Root.
        if ($user->hasRole('Root') && !auth()->user()->isRoot()) {
            abort(403, 'Apenas usuários Root podem editar outros usuários Root.');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->boolean('status', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso.');
    }

    /**
     * Tela de permissões individuais de um usuário (somente Root).
     */
    public function permissions(User $user)
    {
        if ($user->hasRole('Root') && !auth()->user()->isRoot()) {
            abort(403, 'Apenas usuários Root podem editar outros usuários Root.');
        }

        $allPermissions = Permission::orderBy('name')->get();
        $permissionGroups = $allPermissions->groupBy(fn ($p) => explode('.', $p->name)[0] ?? 'outros');

        $allRoles = Role::orderBy('name')->get();
        $currentRole = $user->getRoleNames()->first();

        // Permissões efetivas do usuário (união: perfil + diretas)
        $currentPermissionNames = $user->getAllPermissions()->pluck('name');

        return view('users.permissions', compact(
            'user',
            'allPermissions',
            'permissionGroups',
            'currentPermissionNames',
            'allRoles',
            'currentRole'
        ));
    }

    /**
     * Troca o perfil (role) do usuário.
     */
    public function changeRole(Request $request, User $user)
    {
        if ($user->hasRole('Root') && !auth()->user()->isRoot()) {
            abort(403, 'Apenas usuários Root podem editar outros usuários Root.');
        }

        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        // Só Root pode atribuir o perfil Root a alguém
        if ($data['role'] === 'Root' && !auth()->user()->isRoot()) {
            abort(403, 'Apenas usuários Root podem atribuir o perfil Root.');
        }

        // Captura role personalizado antigo deste user (se houver) antes de sobrescrever
        $oldCustom = $user->roles->first(fn ($r) => str_starts_with($r->name, 'Personalizado-'));

        // Limpa permissões diretas (vamos usar só role)
        $user->syncPermissions([]);
        $user->syncRoles([$data['role']]);

        // Se o perfil personalizado antigo ficou sem dono, remove
        if ($oldCustom && $oldCustom->users()->count() === 0) {
            $oldCustom->delete();
        }

        return redirect()
            ->route('users.permissions.edit', $user)
            ->with('success', "Perfil alterado para \"{$data['role']}\" com sucesso.");
    }

    /**
     * Aplica o conjunto de permissões marcado:
     *  - Se bate exatamente com um perfil PADRÃO (Root/Administrador/Operador/Consulta),
     *    atribui esse perfil ao usuário.
     *  - Caso contrário, cria/atualiza um perfil "Personalizado-{nome do user}"
     *    com exatamente essas permissões e atribui ao usuário.
     */
    public function syncPermissions(Request $request, User $user)
    {
        if (!auth()->user()->isRoot()) {
            abort(403);
        }

        if ($user->hasRole('Root')) {
            return back()->with('error', 'O perfil Root tem todas as permissões e não pode ser alterado por aqui.');
        }

        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $selected = collect($data['permissions'] ?? [])->unique()->sort()->values();

        // Roles padrão (não podem ser sobrescritas). Root também é padrão mas só Root atribui.
        $standardRoleNames = ['Administrador', 'Operador', 'Consulta'];

        // Tenta achar um perfil padrão cujas permissões batem exatamente
        $matchedRole = null;
        foreach ($standardRoleNames as $name) {
            $role = Role::with('permissions')->where('name', $name)->first();
            if (!$role) continue;
            $rolePerms = $role->permissions->pluck('name')->sort()->values();
            if ($rolePerms->all() === $selected->all()) {
                $matchedRole = $role;
                break;
            }
        }

        // Captura role personalizado anterior (se houver) — para reaproveitar ou limpar
        $oldCustom = $user->roles->first(fn ($r) => str_starts_with($r->name, 'Personalizado-'));

        // Limpa permissões DIRETAS (vamos trabalhar só com roles)
        $user->syncPermissions([]);

        $resultMsg = '';

        if ($matchedRole) {
            // Bate com um perfil padrão → atribui esse perfil
            $user->syncRoles([$matchedRole->name]);
            $resultMsg = "Permissões correspondem ao perfil padrão \"{$matchedRole->name}\". Perfil aplicado.";

            // Limpa role personalizado antigo se não for mais usado por ninguém
            if ($oldCustom && $oldCustom->users()->count() === 0) {
                $oldCustom->delete();
            }
        } else {
            // Cria/atualiza perfil personalizado deste usuário
            $baseName = 'Personalizado-' . $user->name;
            // Em caso de colisão (nome igual ao de outro usuário), sufixa com id
            $existingByName = Role::where('name', $baseName)->first();
            if ($existingByName && $oldCustom?->id !== $existingByName->id) {
                $baseName .= '-' . $user->id;
            }

            if ($oldCustom) {
                // Reaproveita o role antigo (rename + sync)
                $oldCustom->name = $baseName;
                $oldCustom->save();
                $oldCustom->syncPermissions($selected->all());
                $customRole = $oldCustom;
            } else {
                $customRole = Role::firstOrCreate(['name' => $baseName, 'guard_name' => 'web']);
                $customRole->syncPermissions($selected->all());
            }

            $user->syncRoles([$customRole->name]);
            $resultMsg = "Perfil personalizado \"{$customRole->name}\" criado/atualizado com as permissões selecionadas.";
        }

        return redirect()
            ->route('users.permissions.edit', $user)
            ->with('success', $resultMsg);
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->can('users.delete'), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir seu próprio usuário.');
        }

        // Usuário Root só pode ser removido por outro Root.
        if ($user->hasRole('Root') && !auth()->user()->isRoot()) {
            return back()->with('error', 'Apenas usuários Root podem excluir outros usuários Root.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário removido com sucesso.');
    }
}
