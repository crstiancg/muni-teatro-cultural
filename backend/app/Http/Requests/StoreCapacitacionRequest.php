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
            // diplomado y programa siguen en el enum de la base por los registros existentes
            'capacitacion.tipo' => 'required|in:especializacion,curso,taller,seminario,conferencias,reconocimiento,capacitacion,otros',
            'capacitacion.nombre_evento' => 'required|string|max:255',
            // opcional: no toda capacitación o reconocimiento la da un centro de estudios
            'capacitacion.centro_estudios' => 'nullable|string|max:255',
            'capacitacion.horas' => 'nullable|integer|min:0',
            'capacitacion.folio' => 'nullable|string|max:255',
            'capacitacion.fecha' => 'nullable|date',
            // el archivo es opcional al editar (se conserva el anterior si no se manda uno nuevo)
            'capacitacion.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
