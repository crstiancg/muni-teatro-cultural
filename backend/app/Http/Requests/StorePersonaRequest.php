<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $persona = $this->route('persona');
        $personaId = $persona?->id;
        $userId = $persona?->user_id;

        return [
            'persona.dni' => 'required|digits:8|unique:personas,dni,' . $personaId,
            'persona.nombre' => 'required',
            'persona.apellido_paterno' => 'required',
            'persona.apellido_materno' => 'required',
            // el correo vive en dos tablas (personas.correo y users.email) porque
            // todavía no están sincronizadas automáticamente en cada guardado.
            'persona.correo' => 'required|email|unique:personas,correo,' . $personaId . '|unique:users,email,' . $userId,
            'persona.genero' => 'nullable|in:masculino,femenino,sin especificar',
            'persona.estado_civil' => 'nullable|in:soltero,casado,divorciado,viudo',
            'persona.fecha_nacimiento' => 'nullable|date',
            'persona.direccion' => 'nullable|string',
            'persona.celular' => 'nullable|digits:9',
            'persona.celular_emergencia' => 'nullable|digits:9',
            'persona.ubigeo_cod_nacimiento' => 'nullable|exists:ubigeos,codigo',
            'persona.ubigeo_cod_residencia' => 'nullable|exists:ubigeos,codigo',
            'persona.codigo_comision' => 'nullable|exists:comisions,codigo,tipo,familia',
            'persona.codigo_comision_alternativo' => 'nullable|exists:comisions,codigo,tipo,familia|different:persona.codigo_comision',
        ];
    }
}
