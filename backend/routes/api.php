<?php

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\CapacitacionController;
use App\Http\Controllers\ComisionController;
use App\Http\Controllers\FormacionAcademicaController;
use App\Http\Controllers\MiInformacionController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\PersonaPublicaController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UbigeoController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NivelController;

// endpoints públicos, sin auth: solo datos seguros para la galería de consejeros
Route::get('publico/portada', [PersonaPublicaController::class, 'portada']);
Route::get('publico/consejeros', [PersonaPublicaController::class, 'index']);
Route::get('publico/consejeros/grupos', [PersonaPublicaController::class, 'grupos']);
Route::get('publico/consejeros/destacadas', [PersonaPublicaController::class, 'actividadesDestacadas']);
Route::get('publico/consejeros/{persona}', [PersonaPublicaController::class, 'show']);

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

    Route::get('comisiones', [ComisionController::class, 'index']);
    Route::get('comisiones/{codigo}', [ComisionController::class, 'show'])->whereAlphaNumeric('codigo');
    Route::post('grupos', [ComisionController::class, 'storeGrupo'])->middleware([HandlePrecognitiveRequests::class]);
    Route::post('familias', [ComisionController::class, 'storeFamilia'])->middleware([HandlePrecognitiveRequests::class]);
    Route::put('comisiones/{comision}', [ComisionController::class, 'update'])->middleware([HandlePrecognitiveRequests::class]);
    Route::delete('comisiones/{comision}', [ComisionController::class, 'destroy']);

    Route::get('mi-informacion', [MiInformacionController::class, 'show']);
    Route::put('mi-informacion', [MiInformacionController::class, 'update'])->middleware([HandlePrecognitiveRequests::class]);
    Route::put('mi-usuario', [MiInformacionController::class, 'updateUsuario'])->middleware([HandlePrecognitiveRequests::class]);
    Route::put('mi-password', [MiInformacionController::class, 'updatePassword'])->middleware([HandlePrecognitiveRequests::class]);

    // POST (no PUT) porque PHP no llena $_FILES en requests PUT con multipart/form-data
    Route::post('mi-informacion/curriculum-vitaes', [MiInformacionController::class, 'storeFormacion']);
    Route::post('mi-informacion/curriculum-vitaes/{formacionAcademica}', [MiInformacionController::class, 'updateFormacion']);
    Route::delete('mi-informacion/curriculum-vitaes/{formacionAcademica}', [MiInformacionController::class, 'destroyFormacion']);
    Route::put('mi-informacion/curriculum-vitaes/{formacionAcademica}/reactivar', [MiInformacionController::class, 'reactivarFormacion']);

    Route::post('personas/{persona}/curriculum-vitaes', [FormacionAcademicaController::class, 'store']);
    Route::post('personas/{persona}/curriculum-vitaes/{formacionAcademica}', [FormacionAcademicaController::class, 'update']);
    Route::delete('personas/{persona}/curriculum-vitaes/{formacionAcademica}', [FormacionAcademicaController::class, 'destroy']);
    Route::put('personas/{persona}/curriculum-vitaes/{formacionAcademica}/reactivar', [FormacionAcademicaController::class, 'reactivar']);

    Route::post('mi-informacion/capacitaciones', [MiInformacionController::class, 'storeCapacitacion']);
    Route::post('mi-informacion/capacitaciones/{capacitacion}', [MiInformacionController::class, 'updateCapacitacion']);
    Route::delete('mi-informacion/capacitaciones/{capacitacion}', [MiInformacionController::class, 'destroyCapacitacion']);
    Route::put('mi-informacion/capacitaciones/{capacitacion}/reactivar', [MiInformacionController::class, 'reactivarCapacitacion']);

    Route::post('personas/{persona}/capacitaciones', [CapacitacionController::class, 'store']);
    Route::post('personas/{persona}/capacitaciones/{capacitacion}', [CapacitacionController::class, 'update']);
    Route::delete('personas/{persona}/capacitaciones/{capacitacion}', [CapacitacionController::class, 'destroy']);
    Route::put('personas/{persona}/capacitaciones/{capacitacion}/reactivar', [CapacitacionController::class, 'reactivar']);

    Route::post('mi-informacion/actividades', [MiInformacionController::class, 'storeActividad']);
    Route::post('mi-informacion/actividades/{actividad}', [MiInformacionController::class, 'updateActividad']);
    Route::delete('mi-informacion/actividades/{actividad}', [MiInformacionController::class, 'destroyActividad']);
    Route::put('mi-informacion/actividades/{actividad}/reactivar', [MiInformacionController::class, 'reactivarActividad']);
    Route::delete('mi-informacion/actividades/{actividad}/permanente', [MiInformacionController::class, 'destroyActividadPermanente']);

    Route::post('personas/{persona}/actividades', [ActividadController::class, 'store']);
    Route::post('personas/{persona}/actividades/{actividad}', [ActividadController::class, 'update']);
    Route::delete('personas/{persona}/actividades/{actividad}', [ActividadController::class, 'destroy']);
    Route::put('personas/{persona}/actividades/{actividad}/reactivar', [ActividadController::class, 'reactivar']);
    Route::delete('personas/{persona}/actividades/{actividad}/permanente', [ActividadController::class, 'destroyPermanente']);
});
