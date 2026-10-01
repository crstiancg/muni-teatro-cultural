<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormacionAcademicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // primaria, secundaria, maestria y doctorado siguen en el enum de la base
            // por los registros existentes, pero ya no se aceptan
            'formacion.tipo' => 'required|in:tecnica_basica,tecnica_superior,universitaria',
            'formacion.nivel_alcanzado' => 'nullable|in:egresado,tecnico,bachiller,titulado,maestria,doctorado',
            'formacion.centro_estudios' => 'required|string|max:255',
            'formacion.profesion' => 'nullable|string|max:255',
            'formacion.folio' => 'nullable|string|max:255',
            'formacion.fecha_expedicion' => 'nullable|date',
            // el archivo es opcional al editar (se conserva el anterior si no se manda uno nuevo)
            'formacion.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
