<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StoreActividadRequest;
use App\Models\Actividad;
use App\Support\Html;
use App\Models\Persona;

class ActividadController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', [
            'editar' => ['store', 'update', 'destroy', 'reactivar'],
            'eliminar' => ['destroyPermanente'],
        ]);
    }

    public function store(StoreActividadRequest $request, Persona $persona)
    {
        $actividad = Actividad::create([
            'titulo' => data_get($request, 'actividad.titulo'),
            'descripcion' => Html::limpio(data_get($request, 'actividad.descripcion')),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
            'persona_id' => $persona->id,
        ]);
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad, 201);
    }

    public function update(StoreActividadRequest $request, Persona $persona, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $persona->id, 404);

        $actividad->update([
            'titulo' => data_get($request, 'actividad.titulo'),
            'descripcion' => Html::limpio(data_get($request, 'actividad.descripcion')),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
        ]);
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y la imagen adjunta, solo se oculta de la vista por defecto
    public function destroy(Persona $persona, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $persona->id, 404);

        $actividad->update(['flag_activo' => false]);

        return response()->json($actividad);
    }

    public function reactivar(Persona $persona, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $persona->id, 404);

        $actividad->update(['flag_activo' => true]);

        return response()->json($actividad);
    }

    // esto sí borra en serio: el registro y la imagen. Se usa desde "Mostrar
    // Anulados" como segundo paso, después de anular, para evitar borrados accidentales
    public function destroyPermanente(Persona $persona, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $persona->id, 404);

        $actividad->eliminarAdjunto();

        return response()->json($actividad->delete());
    }
}
