<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonaRequest;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonaController extends Controller
{
    private const CAMPOS = [
        'dni', 'nombre', 'apellido_paterno', 'apellido_materno', 'genero',
        'fecha_nacimiento', 'direccion', 'estado_civil', 'celular',
        'celular_emergencia', 'correo', 'ubigeo_cod_nacimiento', 'ubigeo_cod_residencia',
        'codigo_comision',
    ];

    public function index(Request $request)
    {
        return $this->generateViewSetList(
            $request,
            Persona::query()->with('user:id,name,email'),
            [],
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
        return response()->json($persona->load(['user:id,name,email', 'ubigeoNacimiento', 'ubigeoResidencia', 'comision']));
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

    public function destroy(Persona $persona)
    {
        return response()->json($persona->delete());
    }
}
