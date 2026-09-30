<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\StoreCapacitacionRequest;
use App\Http\Requests\StoreFormacionAcademicaRequest;
use App\Http\Requests\StoreMiInformacionRequest;
use App\Http\Requests\StoreMiPasswordRequest;
use App\Http\Requests\StoreMiUsuarioRequest;
use App\Http\Requests\StorePersonaFotoRequest;
use App\Models\Actividad;
use App\Models\Capacitacion;
use App\Models\FormacionAcademica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MiInformacionController extends Controller
{
    private const CAMPOS = [
        'dni', 'nombre', 'apellido_paterno', 'apellido_materno', 'genero',
        'fecha_nacimiento', 'direccion', 'estado_civil', 'celular',
        'celular_emergencia', 'correo', 'ubigeo_cod_nacimiento', 'ubigeo_cod_residencia',
        'codigo_comision', 'codigo_comision_alternativo',
    ];

    private const CAMPOS_FORMACION = [
        'tipo', 'nivel_alcanzado', 'centro_estudios', 'profesion', 'folio', 'fecha_expedicion',
    ];

    private const CAMPOS_CAPACITACION = [
        'tipo', 'nombre_evento', 'centro_estudios', 'horas', 'folio', 'fecha',
    ];

    public function show(Request $request)
    {
        $usuario = $request->user();
        // un admin sin ficha de persona también puede entrar a "Mi Perfil"
        $persona = $usuario->persona?->load(['ubigeoNacimiento', 'ubigeoResidencia', 'comision', 'comisionAlternativo', 'formacionesAcademicas', 'capacitaciones', 'actividades', 'foto', 'revisiones.usuario:id,name']);

        return response()->json([
            'usuario' => ['id' => $usuario->id, 'name' => $usuario->name, 'email' => $usuario->email],
            'persona' => $persona,
            // la clave inicial es el DNI: el dashboard recomienda cambiarla mientras lo siga siendo
            'password_es_dni' => $persona && Hash::check($persona->dni, $usuario->password),
        ]);
    }

    public function updateUsuario(StoreMiUsuarioRequest $request)
    {
        $usuario = $request->user();
        $usuario->update(data_get($request, 'usuario'));

        return response()->json($usuario);
    }

    public function update(StoreMiInformacionRequest $request)
    {
        $persona = $request->user()->persona;
        $datos = data_get($request, 'persona');

        $persona->update([
            ...collect($datos)->only(self::CAMPOS)->toArray(),
            'nombre_completo' => trim("{$datos['nombre']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
        ]);

        if (data_get($datos, 'correo_modificado')) {
            $persona->user()->update(['email' => $persona->correo]);
        }

        // la comisión decide en qué disciplina aparece en el portal: si un perfil
        // publicado la cambia, vuelve a revisión (el front ya le avisó antes)
        if ($persona->estado === 'aprobado' && $persona->wasChanged(['codigo_comision', 'codigo_comision_alternativo'])) {
            $persona->update(['estado' => 'pendiente', 'observacion' => null]);
            $persona->revisiones()->create([
                'accion' => 'enviado',
                'observacion' => 'Cambió de comisión: el perfil volvió a revisión.',
                'user_id' => $request->user()->id,
            ]);
            $persona->notificarAdmins('solicitud', "{$persona->nombre_completo} cambió de comisión y su perfil volvió a revisión.");
        }

        return response()->json($persona);
    }

    public function updatePassword(StoreMiPasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make(data_get($request, 'password.nueva')),
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    public function storeFormacion(StoreFormacionAcademicaRequest $request)
    {
        $persona = $request->user()->persona;
        $datos = data_get($request, 'formacion');

        $formacion = FormacionAcademica::create([
            ...collect($datos)->only(self::CAMPOS_FORMACION)->toArray(),
            'persona_id' => $persona->id,
        ]);
        $formacion->reemplazarAdjunto($request->file('formacion.archivo'));

        return response()->json($formacion, 201);
    }

    public function updateFormacion(StoreFormacionAcademicaRequest $request, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $request->user()->persona?->id, 404);

        $datos = data_get($request, 'formacion');
        $formacionAcademica->update([
            ...collect($datos)->only(self::CAMPOS_FORMACION)->toArray(),
        ]);
        $formacionAcademica->reemplazarAdjunto($request->file('formacion.archivo'));

        return response()->json($formacionAcademica);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y el archivo adjunto, solo se oculta de la vista por defecto
    public function destroyFormacion(Request $request, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $request->user()->persona?->id, 404);

        $formacionAcademica->update(['flag_activo' => false]);

        return response()->json($formacionAcademica);
    }

    public function reactivarFormacion(Request $request, FormacionAcademica $formacionAcademica)
    {
        abort_unless($formacionAcademica->persona_id === $request->user()->persona?->id, 404);

        $formacionAcademica->update(['flag_activo' => true]);

        return response()->json($formacionAcademica);
    }


    public function storeCapacitacion(StoreCapacitacionRequest $request)
    {
        $persona = $request->user()->persona;
        $datos = data_get($request, 'capacitacion');

        $capacitacion = Capacitacion::create([
            ...collect($datos)->only(self::CAMPOS_CAPACITACION)->toArray(),
            'persona_id' => $persona->id,
        ]);
        $capacitacion->reemplazarAdjunto($request->file('capacitacion.archivo'));

        return response()->json($capacitacion, 201);
    }

    public function updateCapacitacion(StoreCapacitacionRequest $request, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $request->user()->persona?->id, 404);

        $datos = data_get($request, 'capacitacion');
        $capacitacion->update([
            ...collect($datos)->only(self::CAMPOS_CAPACITACION)->toArray(),
        ]);
        $capacitacion->reemplazarAdjunto($request->file('capacitacion.archivo'));

        return response()->json($capacitacion);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y el archivo adjunto, solo se oculta de la vista por defecto
    public function destroyCapacitacion(Request $request, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $request->user()->persona?->id, 404);

        $capacitacion->update(['flag_activo' => false]);

        return response()->json($capacitacion);
    }

    public function reactivarCapacitacion(Request $request, Capacitacion $capacitacion)
    {
        abort_unless($capacitacion->persona_id === $request->user()->persona?->id, 404);

        $capacitacion->update(['flag_activo' => true]);

        return response()->json($capacitacion);
    }


    public function storeFoto(StorePersonaFotoRequest $request)
    {
        // un admin sin ficha de persona no tiene dónde colgar la foto
        $persona = $request->user()->persona;
        abort_unless($persona, 404);

        return response()->json($persona->reemplazarFoto($request->file('foto')), 201);
    }

    public function destroyFoto(Request $request)
    {
        $persona = $request->user()->persona;
        abort_unless($persona, 404);

        $persona->eliminarFoto();

        return response()->json(true);
    }

    public function storeActividad(StoreActividadRequest $request)
    {
        $persona = $request->user()->persona;

        $actividad = Actividad::create([
            'descripcion' => data_get($request, 'actividad.descripcion'),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
            'persona_id' => $persona->id,
        ]);
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad, 201);
    }

    public function updateActividad(StoreActividadRequest $request, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $request->user()->persona?->id, 404);

        $actividad->update([
            'descripcion' => data_get($request, 'actividad.descripcion'),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
        ]);
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad);
    }

    // "eliminar" acá es anular (flag_activo = false): se conserva el registro
    // y la imagen adjunta, solo se oculta de la vista por defecto
    public function destroyActividad(Request $request, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $request->user()->persona?->id, 404);

        $actividad->update(['flag_activo' => false]);

        return response()->json($actividad);
    }

    public function reactivarActividad(Request $request, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $request->user()->persona?->id, 404);

        $actividad->update(['flag_activo' => true]);

        return response()->json($actividad);
    }

    // esto sí borra en serio: el registro y la imagen. Se usa desde "Mostrar
    // Anulados" como segundo paso, después de anular, para evitar borrados accidentales
    public function destroyActividadPermanente(Request $request, Actividad $actividad)
    {
        abort_unless($actividad->persona_id === $request->user()->persona?->id, 404);

        $actividad->eliminarAdjunto();

        return response()->json($actividad->delete());
    }
}
