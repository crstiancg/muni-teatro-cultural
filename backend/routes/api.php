<?php

use App\Http\Controllers\PersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        // TODO: reemplazar por roles/permisos reales cuando se integre un paquete de permisos
        'roles' => [],
        'permisos' => [],
    ]);
})->middleware('auth:api');


Route::apiResource('personas', PersonaController::class);
