<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

// Revisión de agrupaciones por el admin: mismas reglas que los perfiles de
// artista (solo se aprueba lo enviado; se observa en revisión o publicado).
class AgrupacionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('agrupaciones', [
            'index' => ['index', 'show'],
            'aprobar' => ['aprobar', 'observar'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Agrupacion::query()
            ->with(['comision:codigo,nombre', 'logo'])
            ->withCount('integrantes')
            // las que esperan más van primero cuando se filtra la bandeja
            ->orderByRaw("estado = 'pendiente' desc")
            ->latest('updated_at');

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->string('buscar') . '%');
        }

        return response()->json($query->paginate(min((int) $request->input('por_pagina', 15), 50)));
    }

    public function show(Agrupacion $agrupacion)
    {
        $agrupacion->load(['comision:codigo,nombre', 'logo', 'integrantes.persona:id,slug', 'integrantes.persona.foto', 'revisiones.usuario:id,name']);

        return response()->json([
            ...$agrupacion->toArray(),
            'comision' => $agrupacion->comision?->nombre,
            'requisitos' => $agrupacion->requisitos(),
        ]);
    }

    public function aprobar(Request $request, Agrupacion $agrupacion)
    {
        abort_unless($agrupacion->estado === 'pendiente', 422, 'Solo se puede aprobar una agrupación en revisión.');
        abort_unless($agrupacion->codigo_comision, 422, 'La agrupación no tiene comisión asignada.');

        $this->resolver($request, $agrupacion, 'aprobado', null);
        $agrupacion->notificarRepresentantes('agrupacion_aprobado', "La agrupación {$agrupacion->nombre} fue aprobada y ya aparece en el portal.");

        return $this->show($agrupacion);
    }

    public function observar(Request $request, Agrupacion $agrupacion)
    {
        abort_unless(
            in_array($agrupacion->estado, ['pendiente', 'aprobado']),
            422,
            'Solo se puede observar una agrupación en revisión o publicada.'
        );
        $datos = $request->validate(['observacion' => 'required|string|max:1000']);

        $this->resolver($request, $agrupacion, 'observado', $datos['observacion']);
        $agrupacion->notificarRepresentantes('agrupacion_observado', "La agrupación {$agrupacion->nombre} fue observada: {$datos['observacion']}");

        return $this->show($agrupacion);
    }

    private function resolver(Request $request, Agrupacion $agrupacion, string $estado, ?string $observacion): void
    {
        $agrupacion->update([
            'estado' => $estado,
            'observacion' => $observacion,
            'revisado_por' => $request->user()->id,
            'revisado_en' => now(),
        ]);
        $agrupacion->revisiones()->create([
            'accion' => $estado,
            'observacion' => $observacion,
            'user_id' => $request->user()->id,
        ]);
    }
}
