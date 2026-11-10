<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StorePersonaRequest;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas');
    }

    private const CAMPOS = [
        'dni', 'nombre', 'apellido_paterno', 'apellido_materno', 'genero',
        'fecha_nacimiento', 'direccion', 'estado_civil', 'celular',
        'celular_emergencia', 'correo', 'ubigeo_cod_nacimiento', 'ubigeo_cod_residencia',
        'codigo_comision', 'codigo_comision_alternativo',
    ];

    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Persona::query()->with(['user:id,name,email', 'foto']),
            // ?estado=pendiente arma la bandeja de solicitudes del admin
            ['estado'],
            ['dni', 'nombre', 'apellido_paterno', 'apellido_materno', 'correo'],
            ['id', 'dni', 'nombre_completo']
        );
    }

    public function store(StorePersonaRequest $request)
    {
        $datos = data_get($request, 'persona');

        $persona = DB::transaction(function () use ($datos) {
            // El acceso al sistema se crea junto con la persona: el usuario
            // inicia sesión con su correo y, por defecto, su DNI como clave.
            $user = User::create([
                'name' => trim("{$datos['nombre']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
                'email' => $datos['correo'],
                'password' => bcrypt($datos['dni']),
            ]);

            return Persona::create([
                ...collect($datos)->only(self::CAMPOS)->toArray(),
                'nombre_completo' => trim("{$datos['nombre']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
                'user_id' => $user->id,
            ]);
        });

        return response()->json($persona, 201);
    }

    public function show(Persona $persona)
    {
        return response()->json($persona->load(['user:id,name,email', 'ubigeoNacimiento', 'ubigeoResidencia', 'comision', 'comisionAlternativo', 'formacionesAcademicas', 'capacitaciones', 'actividades', 'foto', 'revisiones.usuario:id,name']));
    }

    public function update(StorePersonaRequest $request, Persona $persona)
    {
        $datos = data_get($request, 'persona');

        DB::transaction(function () use ($datos, $persona) {
            $persona->update([
                ...collect($datos)->only(self::CAMPOS)->toArray(),
                'nombre_completo' => trim("{$datos['nombre']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
            ]);

            // el correo vive duplicado en personas.correo y users.email; solo
            // tocamos el user si el frontend detectó que el correo cambió.
            if (data_get($datos, 'correo_modificado')) {
                $persona->user()->update(['email' => $persona->correo]);
            }
        });

        return response()->json($persona);
    }

    // Borra la persona, su usuario de acceso y todos sus archivos. Las filas de
    // CV/capacitaciones/actividades caen por cascadeOnDelete en la base, pero la
    // tabla polimórfica "archivos" no tiene FK: sus filas y el disco van a mano.
    public function destroy(Request $request, Persona $persona)
    {
        // borrar la persona arrastra su usuario: un usuario oculto tampoco se borra por acá
        abort_if(User::esOculto($persona->user_id) && ! User::esOculto($request->user()->id), 404);

        // sin esto un admin con ficha de persona podría dejarse sin acceso
        abort_if($persona->user_id === $request->user()->id, 422, 'No puedes eliminar tu propia ficha.');

        // se juntan antes (después del cascade ya no se llega a ellos) y se
        // borran después del commit: si la transacción falla, no se pierde nada
        $archivos = $persona->archivos()->get()
            ->merge($persona->formacionesAcademicas()->with('archivos')->get()->flatMap->archivos)
            ->merge($persona->capacitaciones()->with('archivos')->get()->flatMap->archivos)
            ->merge($persona->actividades()->with('archivos')->get()->flatMap->archivos);

        DB::transaction(function () use ($persona) {
            $user = $persona->user;
            $persona->delete();
            $user?->notifications()->delete();
            $user?->tokens()->delete();
            // HasRoles de spatie limpia sus roles y permisos al borrarlo
            $user?->delete();
        });

        // uno por uno para que el evento deleting de Archivo borre el disco
        $archivos->each->delete();

        return response()->json(true);
    }
}
