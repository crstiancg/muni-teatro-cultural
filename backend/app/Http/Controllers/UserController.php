<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            User::query(),
            [],
            ['id', 'name', 'email'],
            ['id', 'name', 'email']
        );
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $user->syncRoles($request->rolesSelected ?? []);
        $user->syncPermissions($request->permisosSelected ?? []);

        return response()->json($user, 201);
    }

    public function show(User $usuario)
    {
        return response()->json([
            'user' => $usuario,
            'rolesSelected' => $usuario->roles->pluck('id'),
            'permisosSelected' => $usuario->permissions->pluck('id'),
            'permissionsData' => $usuario->permissions,
        ]);
    }

    public function update(StoreUserRequest $request, User $usuario)
    {
        $usuario->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->filled('password') ? bcrypt($request->password) : $usuario->password,
        ]);
        $usuario->syncRoles($request->rolesSelected ?? []);
        $usuario->syncPermissions($request->permisosSelected ?? []);

        return response()->json($usuario);
    }

    public function destroy(User $usuario)
    {
        return response()->json($usuario->delete());
    }
}
