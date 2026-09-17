<?php

use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UbigeoController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    $user = $request->user();
    $roles = $user->roles;
    $permisosDirectos = $user->getPermissionNames()->toArray();
    $permisos = [];
    foreach ($roles as $rol) {
        $permisos = array_merge($permisos, $rol->permissions->pluck('name')->toArray());
    }
    $permisos = array_values(array_unique(array_merge($permisos, $permisosDirectos)));

    return response()->json([
        'user' => $user,
        'roles' => $roles->pluck('name'),
        'permisos' => $permisos,
    ]);
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('roles', RolController::class)->middleware([HandlePrecognitiveRequests::class]);
    Route::apiResource('permisos', PermisoController::class)->middleware([HandlePrecognitiveRequests::class]);
    Route::apiResource('usuarios', UserController::class)->middleware([HandlePrecognitiveRequests::class]);
    Route::apiResource('personas', PersonaController::class)->middleware([HandlePrecognitiveRequests::class]);
    Route::get('ubigeos', [UbigeoController::class, 'index']);
    Route::get('ubigeos/{codigo}', [UbigeoController::class, 'show']);
});
