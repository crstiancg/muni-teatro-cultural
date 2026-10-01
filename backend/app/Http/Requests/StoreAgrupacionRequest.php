<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgrupacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:150',
            'codigo_comision' => 'nullable|exists:comisions,codigo,tipo,familia',
            // HTML del editor: se sanitiza en el controller (App\Support\Html)
            'descripcion' => 'nullable|string|max:10000',
            'redes_sociales' => 'nullable|array:' . implode(',', Persona::REDES),
            'redes_sociales.*' => 'nullable|url:http,https|max:255',
            // rol del representante dentro de la agrupación (solo al crear)
            'rol' => 'nullable|string|max:60',
        ];
    }
}
