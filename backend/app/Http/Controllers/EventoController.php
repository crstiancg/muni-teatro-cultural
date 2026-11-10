<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoRequest;
use App\Models\Evento;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

// Gestión de la agenda desde el panel. Fase 1: el admin carga los eventos que
// organiza la municipalidad, que se publican directo (sin revisión).
class EventoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('eventos', [
            'index' => ['index', 'show'],
            'crear' => ['store'],
            'editar' => ['update', 'cancelar'],
            'eliminar' => ['destroy'],
        ]);
    }

    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Evento::query()->with(['comision:codigo,cod_grupo,nombre', 'persona:id,nombre_completo', 'agrupacion:id,nombre']),
            ['estado', 'organizador_tipo'],
            ['titulo', 'lugar'],
            ['id', 'titulo', 'fecha_inicio', 'estado']
        );
    }

    public function show(Evento $evento)
    {
        return response()->json($evento->load('comision:codigo,cod_grupo,nombre'));
    }

    public function store(StoreEventoRequest $request)
    {
        $evento = Evento::create([
            ...$this->datos($request),
            'organizador_tipo' => 'municipalidad',
            'estado' => 'aprobado',
            'creado_por' => $request->user()->id,
        ]);
        $evento->reemplazarAdjunto($request->file('evento.imagen'));

        return response()->json($evento, 201);
    }

    // POST y no PUT: PHP no llena $_FILES en un PUT multipart
    public function update(StoreEventoRequest $request, Evento $evento)
    {
        $evento->update($this->datos($request));
        $evento->reemplazarAdjunto($request->file('evento.imagen'));

        return response()->json($evento);
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

    private function datos(StoreEventoRequest $request): array
    {
        $datos = data_get($request->validated(), 'evento');
        $gratuito = $request->boolean('evento.flag_gratuito', true);
        $presencial = $datos['modalidad'] === 'presencial';

        return [
            'titulo' => $datos['titulo'],
            'descripcion' => Html::limpio($datos['descripcion'] ?? null),
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'modalidad' => $datos['modalidad'],
            // un evento virtual no tiene lugar físico, y uno gratuito no tiene costo
            'lugar' => $presencial ? ($datos['lugar'] ?? null) : null,
            'direccion' => $presencial ? ($datos['direccion'] ?? null) : null,
            'enlace' => $datos['enlace'] ?? null,
            'codigo_comision' => $datos['codigo_comision'] ?? null,
            'flag_gratuito' => $gratuito,
            'costo' => $gratuito ? null : $datos['costo'],
            'flag_destacado' => $request->boolean('evento.flag_destacado'),
        ];
    }
}
