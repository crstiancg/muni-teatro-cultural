<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegranteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agrupacion = $this->route('agrupacion');
        $integrante = $this->route('integrante');

        return [
            // obligatorio (decisión del cliente) y único dentro de la agrupación
            'dni' => [
                'required', 'digits:8',
                Rule::unique('agrupacion_integrantes', 'dni')
                    ->where('agrupacion_id', $agrupacion->id)
                    ->ignore($integrante?->id),
            ],
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'rol' => 'nullable|string|max:60',
        ];
    }

    public function messages(): array
    {
        return ['dni.unique' => 'Esta persona ya figura como integrante de la agrupación.'];
    }
}
