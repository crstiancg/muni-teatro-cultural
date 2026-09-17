<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMiInformacionRequest;
use App\Http\Requests\StoreMiPasswordRequest;
use App\Http\Requests\StoreMiUsuarioRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MiInformacionController extends Controller
{
    private const CAMPOS = [
        'dni', 'nombre', 'apellido_paterno', 'apellido_materno', 'genero',
        'fecha_nacimiento', 'direccion', 'estado_civil', 'celular',
        'celular_emergencia', 'correo', 'ubigeo_cod_nacimiento', 'ubigeo_cod_residencia',
        'codigo_comision',
    ];

    public function show(Request $request)
    {
        $usuario = $request->user();
        // un admin sin ficha de persona también puede entrar a "Mi Perfil"
        $persona = $usuario->persona?->load(['ubigeoNacimiento', 'ubigeoResidencia', 'comision']);

        return response()->json([
            'usuario' => ['id' => $usuario->id, 'name' => $usuario->name, 'email' => $usuario->email],
            'persona' => $persona,
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

        return response()->json($persona);
    }

    public function updatePassword(StoreMiPasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make(data_get($request, 'password.nueva')),
        ]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
