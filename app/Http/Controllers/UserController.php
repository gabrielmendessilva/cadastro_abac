<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $roles = Role::orderBy('name')->get();
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

        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        abort_unless(auth()->user()->can('users.edit'), 403);

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

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->can('users.delete'), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode excluir seu próprio usuário.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuário removido com sucesso.');
    }
}
