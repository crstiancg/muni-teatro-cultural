<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('usuarios');
    }

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
