<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermisoRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermisoController extends Controller
{
    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Permission::query(),
            [],
            ['id', 'name'],
            ['id', 'name', 'description']
        );
    }

    public function store(StorePermisoRequest $request)
    {
        $permiso = Permission::create([
            'name' => data_get($request, 'permiso.name'),
            'description' => data_get($request, 'permiso.description'),
            'guard_name' => 'api',
        ]);

        return response()->json($permiso, 201);
    }

    public function show(Permission $permiso)
    {
        return response()->json($permiso);
    }

    public function update(StorePermisoRequest $request, Permission $permiso)
    {
        $permiso->update([
            'name' => data_get($request, 'permiso.name'),
            'description' => data_get($request, 'permiso.description'),
        ]);
        return response()->json($permiso);
    }

    public function destroy(Permission $permiso)
    {
        return response()->json($permiso->delete());
    }
}
