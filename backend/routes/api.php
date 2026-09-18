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
use App\Http\Controllers\ProfesionController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');


Route::apiResource('personas', PersonaController::class);
