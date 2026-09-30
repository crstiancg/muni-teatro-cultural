<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StoreFormacionAcademicaRequest;
use App\Models\FormacionAcademica;
use App\Models\Persona;

class FormacionAcademicaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', ['editar' => ['store', 'update', 'destroy', 'reactivar']]);
    }

    private const CAMPOS = [
        'tipo', 'nivel_alcanzado', 'centro_estudios', 'profesion', 'folio', 'fecha_expedicion',
    ];

    public function store(StoreFormacionAcademicaRequest $request, Persona $persona)
    {
        $datos = data_get($request, 'formacion');

        $formacion = FormacionAcademica::create([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            'persona_id' => $persona->id,
        ]);
        $formacion->reemplazarAdjunto($request->file('formacion.archivo'));

        return response()->json($formacion, 201);
    }

    public function update(StoreFormacionAcademicaRequest $request, Persona $persona, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $persona->id, 404);

        $datos = data_get($request, 'formacion');
        $formacionAcademica->update([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
        ]);
        $formacionAcademica->reemplazarAdjunto($request->file('formacion.archivo'));

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
}
