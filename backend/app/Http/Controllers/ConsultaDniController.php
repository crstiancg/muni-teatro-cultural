<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Support\Reniec;
use Illuminate\Routing\Controllers\HasMiddleware;

// Autocompleta nombre y apellidos desde RENIEC al registrar una persona.
// Primero mira la base: si el DNI ya está registrado, no consulta afuera.
class ConsultaDniController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', ['crear' => ['show']]);
    }

    public function show(string $dni)
    {
        $persona = Persona::where('dni', $dni)->first(['id', 'nombre_completo']);
        if ($persona) {
            return response()->json(['existe' => true, 'persona' => $persona]);
        }

        return response()->json(['existe' => false, ...Reniec::consultar($dni)]);
    }
}
