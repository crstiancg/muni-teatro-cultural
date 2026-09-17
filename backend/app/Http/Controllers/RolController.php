<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            // Los permisos vienen con el rol para poder mostrar en el form de usuario
            // qué permisos hereda de cada rol asignado.
            Role::query()->with('permissions:id,name,description'),
            [],
            ['id', 'name'],
            ['id', 'name']
        );
    }

    public function store(StoreRolRequest $request)
    {
        $rol = Role::create(['name' => data_get($request, 'rol.name'), 'guard_name' => 'api']);

        if (!empty(data_get($request, 'rol.permisosSelected'))) {
            $rol->syncPermissions(data_get($request, 'rol.permisosSelected'));
        }

        return response()->json($rol, 201);
    }

    public function show(Role $role)
    {
        return response()->json([
            'rol' => $role,
            'permisosSelected' => $role->permissions->pluck('id'),
        ]);
    }

    public function update(StoreRolRequest $request, Role $role)
    {
        $role->update(['name' => data_get($request, 'rol.name')]);
        $role->syncPermissions(data_get($request, 'rol.permisosSelected') ?? []);
        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        return response()->json($role->delete());
    }
}
