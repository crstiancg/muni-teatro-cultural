<?php

use App\Http\Controllers\PersonaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TipoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::apiResource('personas', PersonaController::class);
Route::apiResource('tipos', TipoController::class);
