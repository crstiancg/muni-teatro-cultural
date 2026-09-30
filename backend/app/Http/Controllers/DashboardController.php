<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Comision;
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
            'disciplinas' => $this->porDisciplina(),
            'actividades' => Actividad::query()
                ->where('flag_activo', true)
                ->with('persona:id,nombre_completo')
                ->latest()
                ->limit(8)
                ->get(['id', 'persona_id', 'descripcion', 'created_at']),
        ]);
    }

    // artistas registrados por grupo (disciplina), de mayor a menor, con cuántos
    // ya están publicados. Incluye grupos en 0 para ver dónde falta gente.
    private function porDisciplina()
    {
        $conteos = Persona::query()
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            ->groupBy('comisions.cod_grupo')
            ->selectRaw("comisions.cod_grupo, count(*) as total, sum(personas.estado = 'aprobado') as publicados")
            ->get()
            ->keyBy('cod_grupo');

        return Comision::where('tipo', 'grupo')
            ->get(['cod_grupo', 'nombre'])
            ->map(fn (Comision $grupo) => [
                'cod_grupo' => $grupo->cod_grupo,
                'nombre' => $grupo->nombre,
                'total' => (int) ($conteos[$grupo->cod_grupo]->total ?? 0),
                'publicados' => (int) ($conteos[$grupo->cod_grupo]->publicados ?? 0),
            ])
            ->sortByDesc('total')
            ->values();
    }
}
