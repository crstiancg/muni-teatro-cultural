<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

// Agenda pública. Lista BLANCA de campos (datosPublicos): nada interno
// (estado, observación, quién lo cargó) sale al portal.
class EventoPublicoController extends Controller
{
    // un mes en vista de grilla ocupa hasta 6 semanas; con margen, ~2 meses
    private const MAX_DIAS = 62;

    private const RELACIONES = ['comision:codigo,cod_grupo,nombre', 'persona:id,nombre_completo,slug', 'agrupacion:id,nombre,slug'];

    public function index(Request $request)
    {
        $request->validate([
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
            'grupo' => 'nullable|digits:2',
        ]);

        $desde = CarbonImmutable::parse($request->input('desde'));
        $hasta = CarbonImmutable::parse($request->input('hasta'));
        // nunca devolver "toda la tabla": el front pide de a un mes
        abort_if($desde->diffInDays($hasta) > self::MAX_DIAS, 422, 'El rango de fechas es demasiado largo.');

        $query = Evento::query()
            ->publicado()
            ->enRango($desde->toDateString(), $hasta->toDateString())
            ->with(self::RELACIONES)
            ->orderBy('fecha_inicio');

        if ($request->filled('grupo')) {
            $query->whereHas('comision', fn ($q) => $q->where('cod_grupo', $request->string('grupo')));
        }

        return response()->json([
            'data' => $query->get()->map(fn (Evento $e) => $this->datosPublicos($e)),
        ]);
    }

    // portada: los más cercanos desde hoy (incluye los que están en curso)
    public function proximos(Request $request)
    {
        $limite = min(max((int) $request->input('limite', 4), 1), 8);

        $eventos = Evento::query()
            ->publicado()
            ->proximos()
            ->with(self::RELACIONES)
            ->orderBy('fecha_inicio')
            ->limit($limite)
            ->get();

        return response()->json([
            'data' => $eventos->map(fn (Evento $e) => $this->datosPublicos($e)),
        ]);
    }

    // página del evento: /agenda/{id}-{slug} (el front manda solo el id)
    public function show(int $id)
    {
        $evento = Evento::publicado()->with(self::RELACIONES)->findOrFail($id);

        return response()->json([
            'data' => $this->datosPublicos($evento),
            'otros' => $this->otros($evento)->map(fn (Evento $e) => $this->datosPublicos($e)),
        ]);
    }

    // "más eventos": próximos, primero los de la misma disciplina
    private function otros(Evento $evento)
    {
        $grupo = $evento->comision?->cod_grupo;

        return Evento::query()
            ->publicado()
            ->proximos()
            ->whereKeyNot($evento->id)
            ->with(self::RELACIONES)
            ->orderBy('fecha_inicio')
            ->limit(24)
            ->get()
            ->sortBy(fn (Evento $e) => $grupo && $e->comision?->cod_grupo === $grupo ? 0 : 1)
            ->take(6)
            ->values();
    }

    private function datosPublicos(Evento $e): array
    {
        return [
            'id' => $e->id,
            'slug' => $e->slug,
            'ruta' => $e->ruta_publica,
            'titulo' => $e->titulo,
            'descripcion' => $e->descripcion,
            'descripcion_texto' => $e->descripcion_texto,
            'fecha_inicio' => $e->fecha_inicio?->format(Evento::FORMATO_FECHA),
            'fecha_fin' => $e->fecha_fin?->format(Evento::FORMATO_FECHA),
            'modalidad' => $e->modalidad,
            'lugar' => $e->lugar,
            'direccion' => $e->direccion,
            'enlace' => $e->enlace,
            'flag_gratuito' => $e->flag_gratuito,
            'costo' => $e->costo,
            'comision' => $e->comision?->nombre,
            'cod_grupo' => $e->comision?->cod_grupo,
            'organizador' => $e->organizador(),
            'imagen_url' => $e->imagen_url,
            'imagen_miniatura_url' => $e->imagen_miniatura_url,
            'flag_destacado' => $e->flag_destacado,
        ];
    }
}
