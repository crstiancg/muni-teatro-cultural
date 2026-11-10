<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evento.titulo' => 'required|string|max:150',
            // HTML del editor: se sanitiza en el controller (App\Support\Html)
            'evento.descripcion' => 'nullable|string|max:10000',
            'evento.fecha_inicio' => 'required|date',
            'evento.fecha_fin' => 'nullable|date|after_or_equal:evento.fecha_inicio',
            'evento.modalidad' => 'required|in:presencial,virtual',
            'evento.lugar' => 'nullable|required_if:evento.modalidad,presencial|string|max:150',
            'evento.direccion' => 'nullable|string|max:255',
            'evento.enlace' => 'nullable|required_if:evento.modalidad,virtual|url|max:255',
            'evento.codigo_comision' => 'nullable|exists:comisions,codigo,tipo,familia',
            // multipart/form-data: los booleanos llegan como "0"/"1"/"true"/"false";
            // se convierten en el controller con $request->boolean()
            'evento.flag_gratuito' => 'nullable|in:0,1,true,false',
            'evento.costo' => 'nullable|required_if:evento.flag_gratuito,0,false|numeric|min:0|max:99999',
            'evento.flag_destacado' => 'nullable|in:0,1,true,false',
            'evento.imagen' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
