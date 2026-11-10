<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StorePersonaFotoRequest;
use App\Models\Persona;

class PersonaFotoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', ['editar' => ['store', 'destroy']]);
    }

    public function store(StorePersonaFotoRequest $request, Persona $persona)
    {
        return response()->json($persona->reemplazarFoto($request->file('foto')), 201);
    }

    public function destroy(Persona $persona)
    {
        $persona->eliminarFoto();

        return response()->json(true);
    }
}
