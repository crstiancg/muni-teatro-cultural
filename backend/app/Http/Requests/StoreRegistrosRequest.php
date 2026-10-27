<?php

namespace App\Http\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            // "array:claves" rechaza cualquier registro que no este en la lista
            'registros' => 'nullable|array:' . implode(',', Persona::REGISTROS),
            // el RUC tiene 11 digitos; de los otros no se conoce el formato, solo se limita el tamaño
            'registros.ruc' => 'nullable|digits:11',
            'registros.rnp' => 'nullable|string|max:50',
            'registros.rna' => 'nullable|string|max:50',
            'registros.rentoca' => 'nullable|string|max:50',
        ];
    }
    public function attributes(): array
    {
        return collect(Persona::REGISTROS)
            ->mapWithKeys(fn ($r) => ["registros.{$r}" => strtoupper($r)])
            ->all();
    }
}