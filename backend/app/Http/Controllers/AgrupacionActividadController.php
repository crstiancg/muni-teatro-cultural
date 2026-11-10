<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActividadRequest;
use App\Models\Agrupacion;
use App\Models\AgrupacionActividad;
use App\Support\Html;
use Illuminate\Http\Request;

// Actividades de la agrupación, gestionadas por su representante. Mismos
// endpoints y payload que las actividades del artista (ActividadController),
// así el front reutiliza ActividadGallery y ActividadForm sin cambios.
class AgrupacionActividadController extends Controller
{
    public function store(StoreActividadRequest $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        $actividad = $agrupacion->actividades()->create($this->datos($request));
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad, 201);
    }

    public function update(StoreActividadRequest $request, Agrupacion $agrupacion, AgrupacionActividad $actividad)
    {
        $this->propia($request, $agrupacion, $actividad);

        $actividad->update($this->datos($request));
        $actividad->reemplazarAdjunto($request->file('actividad.imagen'));

        return response()->json($actividad);
    }

    // "eliminar" acá es anular: se oculta sin borrar el registro ni la imagen
    public function destroy(Request $request, Agrupacion $agrupacion, AgrupacionActividad $actividad)
    {
        $this->propia($request, $agrupacion, $actividad);
        $actividad->update(['flag_activo' => false]);

        return response()->json($actividad);
    }

    public function reactivar(Request $request, Agrupacion $agrupacion, AgrupacionActividad $actividad)
    {
        $this->propia($request, $agrupacion, $actividad);
        $actividad->update(['flag_activo' => true]);

        return response()->json($actividad);
    }

    public function destroyPermanente(Request $request, Agrupacion $agrupacion, AgrupacionActividad $actividad)
    {
        $this->propia($request, $agrupacion, $actividad);

        return response()->json($actividad->delete());
    }

    private function datos(StoreActividadRequest $request): array
    {
        return [
            'titulo' => data_get($request, 'actividad.titulo'),
            'descripcion' => Html::limpio(data_get($request, 'actividad.descripcion')),
            'flag_publico' => $request->boolean('actividad.flag_publico', true),
        ];
    }

    private function soloRepresentante(Request $request, Agrupacion $agrupacion): void
    {
        abort_unless(
            $agrupacion->esRepresentante($request->user()->persona),
            403,
            'Solo el representante puede gestionar esta agrupación.'
        );
    }

    private function propia(Request $request, Agrupacion $agrupacion, AgrupacionActividad $actividad): void
    {
        $this->soloRepresentante($request, $agrupacion);
        abort_unless($actividad->agrupacion_id === $agrupacion->id, 404);
    }
}
