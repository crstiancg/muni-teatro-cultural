<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actividad.descripcion' => 'required|string',
            // acá solo se acepta imagen (no PDF), a diferencia de las otras secciones
            'actividad.imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            // en multipart/form-data el valor llega como string ("true"/"false"), no como bool nativo;
            // la regla "boolean" de Laravel solo acepta 0/1/"0"/"1"/true/false, así que se valida a mano
            // en el controller con $request->boolean() en vez de acá
            'actividad.flag_publico' => 'nullable|in:0,1,true,false',
        ];
    }
}
