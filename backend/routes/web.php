<?php

use App\Http\Controllers\CompartirPerfilController;
use Illuminate\Support\Facades\Route;
use App\Models\Tipo;

Route::get('/', function () {

    return view('welcome');

});

Route::get('/tipos', function () {

    $tipos = Tipo::all();

    return view('tipos', compact('tipos'));

});

// link para compartir perfiles con vista previa (Open Graph) en WhatsApp/Facebook
Route::get('/compartir/{persona:slug}', CompartirPerfilController::class)->name('compartir.perfil');
