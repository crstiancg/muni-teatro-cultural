<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class StorePerfilPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // el HTML se sanitiza en el modelo; acá solo se limita el tamaño
            'biografia' => 'nullable|string|max:10000',
            // "array:claves" rechaza cualquier red que no esté en la lista
            'redes_sociales' => 'nullable|array:' . implode(',', Persona::REDES),
            'redes_sociales.*' => 'nullable|url:http,https|max:255',
        ];
    }

    public function attributes(): array
    {
        return collect(Persona::REDES)
            ->mapWithKeys(fn ($red) => ["redes_sociales.{$red}" => $red])
            ->all();
    }
}
