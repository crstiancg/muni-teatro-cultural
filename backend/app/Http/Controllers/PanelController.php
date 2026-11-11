<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;

// Datos de la cáscara del panel (no de una pantalla en particular).
class PanelController extends Controller
{
    // Contadores del menú lateral: lo que espera revisión en cada sección.
    // Solo se informa lo que el usuario puede revisar; sin permiso, la clave no viene.
    public function contadores(Request $request)
    {
        $user = $request->user();
        $contadores = [];

        if ($user->can('admin-eventos-aprobar')) {
            $contadores['eventos'] = Evento::where('estado', 'pendiente')->count();
        }
        if ($user->can('admin-agrupaciones-aprobar')) {
            $contadores['agrupaciones'] = Agrupacion::where('estado', 'pendiente')->count();
        }
        if ($user->can('admin-personas-aprobar')) {
            $contadores['personas'] = Persona::where('estado', 'pendiente')->count();
        }

        return response()->json((object) $contadores);
    }
}
