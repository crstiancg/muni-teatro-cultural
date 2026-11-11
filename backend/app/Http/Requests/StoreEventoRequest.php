<?php

namespace App\Http\Requests;

use App\Support\Html;
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
            // solo en "Mis eventos": vacío = organiza la persona; con id = su agrupación
            'evento.agrupacion_id' => 'nullable|integer|exists:agrupaciones,id',
        ];
    }

    // ÚNICA normalización de los datos de un evento (panel del admin y del artista).
    // No incluye organizador, estado ni flag_destacado: eso lo decide cada controller.
    public function datosEvento(): array
    {
        $datos = data_get($this->validated(), 'evento');
        $gratuito = $this->boolean('evento.flag_gratuito', true);
        $presencial = $datos['modalidad'] === 'presencial';

        return [
            'titulo' => $datos['titulo'],
            'descripcion' => Html::limpio($datos['descripcion'] ?? null),
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'] ?? null,
            'modalidad' => $datos['modalidad'],
            // un evento virtual no tiene lugar físico, y uno gratuito no tiene costo
            'lugar' => $presencial ? ($datos['lugar'] ?? null) : null,
            'direccion' => $presencial ? ($datos['direccion'] ?? null) : null,
            'enlace' => $datos['enlace'] ?? null,
            'codigo_comision' => $datos['codigo_comision'] ?? null,
            'flag_gratuito' => $gratuito,
            'costo' => $gratuito ? null : $datos['costo'],
        ];
    }
}
