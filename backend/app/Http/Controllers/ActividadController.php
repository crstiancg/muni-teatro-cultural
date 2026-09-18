<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadRequest;
use App\Models\Actividad;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class ActividadController extends Controller
{
    public function store(StoreActividadRequest $request, Persona $persona)
    {
        $actividad = Actividad::create([
            'descripcion' => data_get($request, 'actividad.descripcion'),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
            'persona_id' => $persona->id,
            ...$this->datosImagen($request),
        ]);

        return response()->json($actividad, 201);
    }

    public function update(StoreActividadRequest $request, Persona $persona, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $persona->id, 404);

        $imagenNueva = $this->datosImagen($request);

        if ($imagenNueva && $actividad->imagen_path) {
            Storage::disk('public')->delete($actividad->imagen_path);
        }

        $actividad->update([
            'descripcion' => data_get($request, 'actividad.descripcion'),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
            ...$imagenNueva,
        ]);

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

        if ($actividad->imagen_path) {
            Storage::disk('public')->delete($actividad->imagen_path);
        }

        return response()->json($actividad->delete());
    }

    private function datosImagen(StoreActividadRequest $request): array
    {
        if (! $request->hasFile('actividad.imagen')) {
            return [];
        }

        $imagen = $request->file('actividad.imagen');

        return [
            'imagen_path' => $imagen->store('actividades', 'public'),
            'imagen_nombre_original' => $imagen->getClientOriginalName(),
        ];
    }
}
