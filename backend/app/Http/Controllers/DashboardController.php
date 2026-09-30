<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Persona;
use Illuminate\Routing\Controllers\HasMiddleware;

// Resumen del panel para el admin: todo en una sola llamada
class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', ['index' => ['admin']]);
    }

    public function admin()
    {
        $porEstado = Persona::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return response()->json([
            // siempre las 4 claves, aunque algún estado tenga 0
            'estados' => collect(['pendiente', 'aprobado', 'observado', 'borrador'])
                ->mapWithKeys(fn ($estado) => [$estado => (int) ($porEstado[$estado] ?? 0)]),
            // las que esperan más van primero: se atienden por orden de llegada
            'pendientes' => Persona::query()
                ->where('estado', 'pendiente')
                ->with('foto')
                ->oldest('updated_at')
                ->limit(6)
                ->get(['id', 'nombre_completo', 'updated_at']),
            'actividades' => Actividad::query()
                ->where('flag_activo', true)
                ->with('persona:id,nombre_completo')
                ->latest()
                ->limit(8)
                ->get(['id', 'persona_id', 'descripcion', 'created_at']),
        ]);
    }
}
