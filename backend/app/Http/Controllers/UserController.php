<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            ...static::permisos('usuarios'),
            new Middleware('permission:admin-usuarios-baja', only: ['baja', 'reactivar']),
        ];
    }

    // Saca al usuario de todos los dispositivos y le bloquea el login. Un usuario
    // oculto ya da 404 antes de llegar acá (User::resolveRouteBinding).
    public function baja(Request $request, User $usuario)
    {
        // darse de baja a uno mismo = quedarse afuera sin nadie que lo reactive
        abort_if($usuario->is($request->user()), 422, 'No puedes darte de baja a ti mismo.');
        $datos = $request->validate(['motivo' => 'required|string|max:500']);

        $usuario->darDeBaja($request->user(), $datos['motivo']);

        return response()->json($usuario->fresh());
    }

    public function reactivar(User $usuario)
    {
        $usuario->reactivar();

        return response()->json($usuario->fresh());
    }

    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            // los usuarios ocultos (config/acceso.php) no aparecen en la lista
            User::query()->visiblesPara($request->user()),
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
