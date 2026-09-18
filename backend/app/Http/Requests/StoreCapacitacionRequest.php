<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCapacitacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'capacitacion.tipo' => 'required|in:diplomado,programa,especializacion,curso,taller,seminario,conferencias,otros',
            'capacitacion.nombre_evento' => 'required|string|max:255',
            'capacitacion.centro_estudios' => 'required|string|max:255',
            'capacitacion.horas' => 'nullable|integer|min:0',
            'capacitacion.folio' => 'nullable|string|max:255',
            'capacitacion.fecha' => 'nullable|date',
            // el archivo es opcional al editar (se conserva el anterior si no se manda uno nuevo)
            'capacitacion.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
