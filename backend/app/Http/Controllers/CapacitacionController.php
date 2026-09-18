<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCapacitacionRequest;
use App\Models\Capacitacion;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class CapacitacionController extends Controller
{
    private const CAMPOS = [
        'tipo', 'nombre_evento', 'centro_estudios', 'horas', 'folio', 'fecha',
    ];

    public function store(StoreCapacitacionRequest $request, Persona $persona)
    {
        $datos = data_get($request, 'capacitacion');

        $capacitacion = Capacitacion::create([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            'persona_id' => $persona->id,
            ...$this->datosArchivo($request),
        ]);

        return response()->json($capacitacion, 201);
    }

    public function update(StoreCapacitacionRequest $request, Persona $persona, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $persona->id, 404);

        $datos = data_get($request, 'capacitacion');
        $archivoNuevo = $this->datosArchivo($request);

        if ($archivoNuevo && $capacitacion->archivo_path) {
            Storage::disk('public')->delete($capacitacion->archivo_path);
        }

        $capacitacion->update([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            ...$archivoNuevo,
        ]);

        return response()->json($capacitacion);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y el archivo adjunto, solo se oculta de la vista por defecto
    public function destroy(Persona $persona, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $persona->id, 404);

        $capacitacion->update(['flag_activo' => false]);

        return response()->json($capacitacion);
    }

    public function reactivar(Persona $persona, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $persona->id, 404);

        $capacitacion->update(['flag_activo' => true]);

        return response()->json($capacitacion);
    }

    private function datosArchivo(StoreCapacitacionRequest $request): array
    {
        if (! $request->hasFile('capacitacion.archivo')) {
            return [];
        }

        $archivo = $request->file('capacitacion.archivo');

        return [
            'archivo_path' => $archivo->store('capacitaciones', 'public'),
            'archivo_nombre_original' => $archivo->getClientOriginalName(),
        ];
    }
}
