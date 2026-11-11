<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventoRequest;
use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;

// Panel del artista: propone SUS próximos eventos (o los de la agrupación que
// representa) y ve en qué estado está cada uno. Todo entra pendiente y lo revisa
// el admin (EventoController@aprobar/observar). Igual que en Mis agrupaciones,
// solo hace falta ficha de persona: cada acción verifica que el evento sea suyo.
class MisEventosController extends Controller
{
    public function index(Request $request)
    {
        $persona = $this->miPersona($request);

        $eventos = Evento::gestionadosPor($persona)
            ->with(['comision:codigo,nombre', 'agrupacion:id,nombre'])
            ->orderByDesc('fecha_inicio')
            ->get();

        return response()->json([
            'eventos' => $eventos,
            // por las que puede proponer (selector "Organiza")
            'agrupaciones' => $this->agrupacionesQueRepresenta($persona)->get(['agrupaciones.id', 'agrupaciones.nombre']),
        ]);
    }

    public function show(Request $request, Evento $evento)
    {
        $this->propio($request, $evento);

        return response()->json($evento->load(['comision:codigo,cod_grupo,nombre', 'agrupacion:id,nombre']));
    }

    public function store(StoreEventoRequest $request)
    {
        $persona = $this->miPersona($request);

        $evento = Evento::create([
            ...$request->datosEvento(),
            ...$this->organizador($request, $persona),
            'estado' => 'pendiente',
            'creado_por' => $request->user()->id,
        ]);
        $evento->reemplazarAdjunto($request->file('evento.imagen'));
        $this->enviarARevision($request, $evento, 'evento_solicitud', "Nuevo evento propuesto para la agenda: \"{$evento->titulo}\".");

        return response()->json($evento, 201);
    }

    // POST y no PUT: PHP no llena $_FILES en un PUT multipart.
    // Editar uno aprobado u observado lo vuelve a revisión (sale de la agenda hasta reaprobarlo).
    // El organizador NO se cambia al editar: el evento sigue siendo de quien lo propuso.
    public function update(StoreEventoRequest $request, Evento $evento)
    {
        $this->propio($request, $evento);
        abort_if($evento->estado === 'cancelado', 422, 'Un evento cancelado ya no se puede editar.');

        $estabaPendiente = $evento->estado === 'pendiente';
        $evento->update([...$request->datosEvento(), 'estado' => 'pendiente', 'observacion' => null]);
        $evento->reemplazarAdjunto($request->file('evento.imagen'));

        if (! $estabaPendiente) {
            $this->enviarARevision($request, $evento, 'evento_actualizacion', "El evento \"{$evento->titulo}\" fue modificado y espera revisión.");
        }

        return response()->json($evento);
    }

    public function cancelar(Request $request, Evento $evento)
    {
        $this->propio($request, $evento);

        $evento->update(['estado' => 'cancelado']);

        return response()->json($evento);
    }

    // solo lo que todavía no salió al portal: lo publicado se cancela (queda el historial)
    public function destroy(Request $request, Evento $evento)
    {
        $this->propio($request, $evento);
        abort_if($evento->estado === 'aprobado', 422, 'Un evento publicado no se elimina: cancélalo.');

        $evento->eliminarAdjunto();

        return response()->json($evento->delete());
    }

    // ---------- helpers ----------

    private function organizador(StoreEventoRequest $request, Persona $persona): array
    {
        $agrupacionId = $request->input('evento.agrupacion_id');
        if (! $agrupacionId) {
            return ['organizador_tipo' => 'persona', 'persona_id' => $persona->id, 'codigo_comision' => $request->input('evento.codigo_comision') ?: $persona->codigo_comision];
        }

        $agrupacion = Agrupacion::findOrFail($agrupacionId);
        abort_unless($agrupacion->esRepresentante($persona), 403, 'Solo el representante puede proponer eventos de esta agrupación.');
        abort_unless($agrupacion->estado === 'aprobado', 422, 'La agrupación tiene que estar aprobada para proponer eventos.');

        return ['organizador_tipo' => 'agrupacion', 'agrupacion_id' => $agrupacion->id, 'codigo_comision' => $request->input('evento.codigo_comision') ?: $agrupacion->codigo_comision];
    }

    private function enviarARevision(Request $request, Evento $evento, string $tipo, string $mensaje): void
    {
        $evento->revisiones()->create(['accion' => 'enviado', 'user_id' => $request->user()->id]);
        $evento->notificarAdmins($tipo, $mensaje);
    }

    private function agrupacionesQueRepresenta(Persona $persona)
    {
        return $persona->agrupaciones()->wherePivot('es_representante', true)->where('agrupaciones.estado', 'aprobado');
    }

    private function miPersona(Request $request): Persona
    {
        $persona = $request->user()->persona;
        abort_unless($persona, 403, 'Necesitas una ficha de persona para proponer eventos.');

        return $persona;
    }

    private function propio(Request $request, Evento $evento): void
    {
        abort_unless($evento->esGestionadoPor($this->miPersona($request)), 403, 'Este evento no es tuyo.');
    }
}
