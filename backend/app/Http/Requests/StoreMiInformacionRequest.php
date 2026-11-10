<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class StoreMiInformacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // acá no hay {persona} en la ruta (es siempre "la mía"), así que el
        // registro a excluir en los unique se resuelve desde el usuario logueado
        $persona = $this->user()->persona;
        $personaId = $persona?->id;
        $userId = $persona?->user_id;

        return [
            'persona.dni' => 'required|digits:8|unique:personas,dni,' . $personaId,
            'persona.nombre' => 'required',
            'persona.apellido_paterno' => 'required',
            'persona.apellido_materno' => 'required',
            'persona.correo' => 'required|email|unique:personas,correo,' . $personaId . '|unique:users,email,' . $userId,
            // a diferencia del alta del admin (StorePersonaRequest), acá la persona
            // tiene que completar todo: ver Persona::DATOS_OBLIGATORIOS
            'persona.genero' => 'required|in:masculino,femenino,sin especificar',
            'persona.estado_civil' => 'required|in:soltero,casado,divorciado,viudo',
            // tiene que responder Sí o No (false también vale); si es Sí, el tipo
            'persona.flag_discapacidad' => 'required|boolean',
            'persona.tipo_discapacidad' => 'nullable|required_if_accepted:persona.flag_discapacidad|in:' . implode(',', Persona::TIPOS_DISCAPACIDAD),
            'persona.fecha_nacimiento' => 'required|date|before:today',
            'persona.direccion' => 'required|string',
            'persona.celular' => 'required|digits:9',
            'persona.flag_celular_publico' => 'nullable|boolean',
            'persona.flag_correo_publico' => 'nullable|boolean',
            'persona.celular_emergencia' => 'required|digits:9',
            'persona.ubigeo_cod_nacimiento' => 'required|exists:ubigeos,codigo',
            'persona.ubigeo_cod_residencia' => 'required|exists:ubigeos,codigo',
            'persona.codigo_comision' => 'required|exists:comisions,codigo,tipo,familia',
            'persona.codigo_comision_alternativo' => 'nullable|exists:comisions,codigo,tipo,familia|different:persona.codigo_comision',
        ];
    }
}
