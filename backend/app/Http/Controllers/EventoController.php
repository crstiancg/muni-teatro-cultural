<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoRequest;
use App\Models\Evento;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

// Gestión de la agenda desde el panel del admin: los eventos de la municipalidad
// (se publican directo) y la revisión de los que proponen los artistas
// (los proponen desde MisEventosController).
class EventoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('eventos', [
            'index' => ['index', 'show'],
            'crear' => ['store'],
            'editar' => ['update', 'cancelar'],
            'eliminar' => ['destroy'],
            'aprobar' => ['aprobar', 'observar'],
        ]);
    }

    // ?cuando=proximos (del más cercano al más lejano, como una agenda) | anteriores (del más reciente)
    // ?organizador=municipalidad | artistas (persona o agrupación)
    public function index(Request $request)
    {
        $query = Evento::query()
            ->with(['comision:codigo,cod_grupo,nombre', 'persona:id,nombre_completo', 'agrupacion:id,nombre']);

        match ($request->input('cuando')) {
            'proximos' => $query->proximos()->orderBy('fecha_inicio'),
            'anteriores' => $query->anteriores()->orderByDesc('fecha_inicio'),
            default => null,
        };

        match ($request->input('organizador')) {
            'municipalidad' => $query->where('organizador_tipo', 'municipalidad'),
            'artistas' => $query->whereIn('organizador_tipo', ['persona', 'agrupacion']),
            default => null,
        };

        return $this->generateViewSetList(
            $request,
            $query,
            ['estado', 'organizador_tipo'],
            ['titulo', 'lugar'],
            ['id', 'titulo', 'fecha_inicio', 'estado']
        );
    }

    public function show(Evento $evento)
    {
        return response()->json($evento->load([
            'comision:codigo,cod_grupo,nombre',
            'persona:id,nombre_completo',
            'agrupacion:id,nombre',
            'revisiones.usuario:id,name',
        ]));
    }

    public function store(StoreEventoRequest $request)
    {
        $evento = Evento::create([
            ...$request->datosEvento(),
            'flag_destacado' => $request->boolean('evento.flag_destacado'),
            'organizador_tipo' => 'municipalidad',
            'estado' => 'aprobado',
            'creado_por' => $request->user()->id,
        ]);
        $evento->reemplazarAdjunto($request->file('evento.imagen'));

        return response()->json($evento, 201);
    }

    // POST y no PUT: PHP no llena $_FILES en un PUT multipart.
    // Lo que edita el admin NO vuelve a revisión (él es quien revisa).
    public function update(StoreEventoRequest $request, Evento $evento)
    {
        $evento->update([
            ...$request->datosEvento(),
            'flag_destacado' => $request->boolean('evento.flag_destacado'),
        ]);
        $evento->reemplazarAdjunto($request->file('evento.imagen'));

        return response()->json($evento);
    }

    public function aprobar(Request $request, Evento $evento)
    {
        abort_unless($evento->estado === 'pendiente', 422, 'Solo se puede aprobar un evento en revisión.');

        $this->resolver($request, $evento, 'aprobado', null);
        $evento->notificarOrganizador('evento_aprobado', "Tu evento \"{$evento->titulo}\" fue aprobado y ya aparece en la agenda cultural.");

        return $this->show($evento);
    }

    // también sirve para bajar uno ya publicado que tiene un problema
    public function observar(Request $request, Evento $evento)
    {
        abort_unless(
            in_array($evento->estado, ['pendiente', 'aprobado']),
            422,
            'Solo se puede observar un evento en revisión o publicado.'
        );
        $datos = $request->validate(['observacion' => 'required|string|max:1000']);

        $this->resolver($request, $evento, 'observado', $datos['observacion']);
        $evento->notificarOrganizador('evento_observado', "Tu evento \"{$evento->titulo}\" fue observado: {$datos['observacion']}");

        return $this->show($evento);
    }

    // sale de la agenda pública pero queda en el panel (historial)
    public function cancelar(Evento $evento)
    {
        $evento->update(['estado' => 'cancelado']);

        return response()->json($evento);
    }

    public function destroy(Evento $evento)
    {
        $evento->eliminarAdjunto();

        return response()->json($evento->delete());
    }

    private function resolver(Request $request, Evento $evento, string $estado, ?string $observacion): void
    {
        $evento->update([
            'estado' => $estado,
            'observacion' => $observacion,
            'revisado_por' => $request->user()->id,
            'revisado_en' => now(),
        ]);
        $evento->revisiones()->create([
            'accion' => $estado,
            'observacion' => $observacion,
            'user_id' => $request->user()->id,
        ]);
    }
}
