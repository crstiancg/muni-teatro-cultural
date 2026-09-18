<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormacionAcademicaRequest;
use App\Models\FormacionAcademica;
use App\Models\Persona;
use Illuminate\Support\Facades\Storage;

class FormacionAcademicaController extends Controller
{
    private const CAMPOS = [
        'tipo', 'nivel_alcanzado', 'centro_estudios', 'profesion', 'folio', 'fecha_expedicion',
    ];

    public function store(StoreFormacionAcademicaRequest $request, Persona $persona)
    {
        $datos = data_get($request, 'formacion');

        $formacion = FormacionAcademica::create([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            'persona_id' => $persona->id,
            ...$this->datosArchivo($request),
        ]);

        return response()->json($formacion, 201);
    }

    public function update(StoreFormacionAcademicaRequest $request, Persona $persona, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $persona->id, 404);

        $datos = data_get($request, 'formacion');
        $archivoNuevo = $this->datosArchivo($request);

        if ($archivoNuevo && $formacionAcademica->archivo_path) {
            Storage::disk('public')->delete($formacionAcademica->archivo_path);
        }

        $formacionAcademica->update([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            ...$archivoNuevo,
        ]);

        return response()->json($formacionAcademica);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y el archivo adjunto, solo se oculta de la vista por defecto
    public function destroy(Persona $persona, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $persona->id, 404);

        $formacionAcademica->update(['flag_activo' => false]);

        return response()->json($formacionAcademica);
    }

    public function reactivar(Persona $persona, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $persona->id, 404);

        $formacionAcademica->update(['flag_activo' => true]);

        return response()->json($formacionAcademica);
    }

    private function datosArchivo(StoreFormacionAcademicaRequest $request): array
    {
        if (! $request->hasFile('formacion.archivo')) {
            return [];
        }

        $archivo = $request->file('formacion.archivo');

        return [
            'archivo_path' => $archivo->store('formacion_academica', 'public'),
            'archivo_nombre_original' => $archivo->getClientOriginalName(),
        ];
    }
}
