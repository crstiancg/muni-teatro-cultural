<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamiliaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comision.nombre' => 'required',
            'comision.cod_grupo' => 'required|exists:comisions,cod_grupo,tipo,grupo',
        ];
    }
}
