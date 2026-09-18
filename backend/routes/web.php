<?php

use Illuminate\Support\Facades\Route;
use App\Models\Tipo;

Route::get('/', function () {

    return view('welcome');

});

Route::get('/tipos', function () {

    $tipos = Tipo::all();

    return view('tipos', compact('tipos'));

});