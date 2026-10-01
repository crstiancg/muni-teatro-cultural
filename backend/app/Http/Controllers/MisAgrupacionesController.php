<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgrupacionRequest;
use App\Http\Requests\StoreIntegranteRequest;
use App\Http\Requests\StorePersonaFotoRequest;
use App\Models\Agrupacion;
use App\Models\AgrupacionIntegrante;
use App\Models\Persona;
use App\Support\Html;
use App\Support\Reniec;
use Illuminate\Http\Request;

// Panel del artista: las agrupaciones donde figura y, si es representante,
// su gestión completa (datos, logo, integrantes y envío a revisión).
// Solo hace falta estar logueado con ficha de persona: cada acción de gestión
// verifica que sea representante de ESA agrupación.
class MisAgrupacionesController extends Controller
{
    public function index(Request $request)
    {
        $persona = $this->miPersona($request);

        $agrupaciones = $persona->agrupaciones()
            ->with(['comision:codigo,nombre', 'logo', 'portada'])
            ->withCount(['integrantes', 'actividades'])
            ->orderByDesc('agrupacion_integrantes.es_representante')
            ->orderBy('nombre')
            ->get()
            ->map(function (Agrupacion $a) {
                $esRepresentante = (bool) $a->pivot->es_representante;

                return [
                    ...$a->only(['id', 'nombre', 'slug', 'estado', 'codigo_comision', 'observacion']),
                    'comision' => $a->comision?->nombre,
                    'logo' => $a->logo,
                    'portada_url' => $a->portada?->miniatura_url,
                    'rol' => $a->pivot->rol,
                    'es_representante' => $esRepresentante,
                    'total_integrantes' => $a->integrantes_count,
                    'total_actividades' => $a->actividades_count,
                    // lo obligatorio que falta para poder enviarla (solo le sirve al representante)
                    'faltan' => $esRepresentante
                        ? collect($a->requisitos())->filter(fn ($r) => $r['obligatorio'] && ! $r['cumple'])->pluck('label')->values()
                        : [],
                ];
            });

        return response()->json([
            'agrupaciones' => $agrupaciones,
            'representadas' => $agrupaciones->where('es_representante', true)->count(),
            'limite' => config('agrupaciones.max_por_representante'),
        ]);
    }

    public function show(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        return response()->json($this->detalle($agrupacion));
    }

    public function store(StoreAgrupacionRequest $request)
    {
        $persona = $this->miPersona($request);

        // tope por persona (config/agrupaciones.php): ser integrante de otras no cuenta
        $limite = config('agrupaciones.max_por_representante');
        $representadas = $persona->agrupaciones()->wherePivot('es_representante', true)->count();
        abort_if(
            $representadas >= $limite,
            422,
            $limite === 1
                ? 'Solo puedes crear una agrupación.'
                : "Puedes representar hasta {$limite} agrupaciones y ya llegaste al límite."
        );

        $agrupacion = Agrupacion::create($this->datos($request));

        // quien la crea queda como representante (y primer integrante)
        $agrupacion->integrantes()->create([
            'dni' => $persona->dni,
            'nombre' => $persona->nombre,
            'apellido_paterno' => $persona->apellido_paterno,
            'apellido_materno' => $persona->apellido_materno,
            'rol' => $request->input('rol') ?: 'Representante',
            'es_representante' => true,
        ]);

        return response()->json($this->detalle($agrupacion), 201);
    }

    public function update(StoreAgrupacionRequest $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        $agrupacion->update($this->datos($request));

        // publicada: cambiar datos la devuelve a revisión (el front ya avisó)
        $cambios = collect([
            'nombre' => 'nombre', 'codigo_comision' => 'comisión',
            'descripcion' => 'descripción', 'redes_sociales' => 'redes sociales',
        ])->filter(fn ($_, $campo) => $agrupacion->wasChanged($campo));
        if ($cambios->isNotEmpty()) {
            $agrupacion->volverARevision('Cambió ' . $cambios->implode(', ') . '.', $request->user()->id);
        }

        return response()->json($this->detalle($agrupacion));
    }

    public function enviarRevision(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        abort_unless(
            in_array($agrupacion->estado, ['borrador', 'observado']),
            422,
            $agrupacion->estado === 'pendiente' ? 'La agrupación ya está en revisión.' : 'La agrupación ya está publicada.'
        );

        $faltan = collect($agrupacion->requisitos())
            ->filter(fn ($r) => $r['obligatorio'] && ! $r['cumple'])
            ->pluck('label');
        abort_if($faltan->isNotEmpty(), 422, 'Antes de enviar completa: ' . $faltan->implode(', ') . '.');

        $agrupacion->update(['estado' => 'pendiente', 'observacion' => null]);
        $agrupacion->revisiones()->create(['accion' => 'enviado', 'user_id' => $request->user()->id]);
        $agrupacion->notificarAdmins('agrupacion_solicitud', "La agrupación {$agrupacion->nombre} se envió a revisión.");

        return response()->json($this->detalle($agrupacion));
    }

    // ---------- logo (mismo endpoint "/foto" que usa FotoPerfilUploader) ----------

    public function storeLogo(StorePersonaFotoRequest $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        return response()->json($agrupacion->reemplazarLogo($request->file('foto')), 201);
    }

    public function destroyLogo(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);
        $agrupacion->eliminarLogo();

        return response()->json(true);
    }

    // ---------- portada (imagen horizontal de cabecera) ----------

    public function storePortada(StorePersonaFotoRequest $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        return response()->json($agrupacion->reemplazarPortada($request->file('foto')), 201);
    }

    public function destroyPortada(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);
        $agrupacion->eliminarPortada();

        return response()->json(true);
    }

    // ---------- integrantes ----------

    // autocompletar al cargar un integrante: primero la base, después RENIEC
    public function consultarDni(Request $request, string $dni)
    {
        $this->miPersona($request);

        $persona = Persona::where('dni', $dni)->first(['nombre', 'apellido_paterno', 'apellido_materno']);
        if ($persona) {
            return response()->json(['registrado' => true, 'dni' => $dni, ...$persona->toArray()]);
        }

        return response()->json(['registrado' => false, ...Reniec::consultar($dni)]);
    }

    public function storeIntegrante(StoreIntegranteRequest $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        $integrante = $agrupacion->integrantes()->create($request->validated());
        $agrupacion->volverARevision("Agregó al integrante {$integrante->nombre_completo}.", $request->user()->id);

        return response()->json($integrante->load('persona:id,slug'), 201);
    }

    public function updateIntegrante(StoreIntegranteRequest $request, Agrupacion $agrupacion, AgrupacionIntegrante $integrante)
    {
        $this->soloRepresentante($request, $agrupacion);
        abort_unless($integrante->agrupacion_id === $agrupacion->id, 404);

        // el DNI del representante es el de su ficha: no se cambia desde acá
        $datos = $integrante->es_representante
            ? collect($request->validated())->only('rol')->all()
            : $request->validated();
        $integrante->update($datos);
        if ($integrante->wasChanged()) {
            $agrupacion->volverARevision("Modificó al integrante {$integrante->nombre_completo}.", $request->user()->id);
        }

        return response()->json($integrante->load('persona:id,slug'));
    }

    public function destroyIntegrante(Request $request, Agrupacion $agrupacion, AgrupacionIntegrante $integrante)
    {
        $this->soloRepresentante($request, $agrupacion);
        abort_unless($integrante->agrupacion_id === $agrupacion->id, 404);
        // sin representante nadie podría gestionarla
        abort_if($integrante->es_representante, 422, 'No se puede quitar al representante de la agrupación.');

        $integrante->delete();
        $agrupacion->volverARevision("Quitó al integrante {$integrante->nombre_completo}.", $request->user()->id);

        return response()->json(true);
    }

    // el representante solo borra lo que nunca envió: una vez enviada o
    // publicada, eliminarla pasa por el admin
    public function destroy(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);
        abort_unless(
            $agrupacion->estado === 'borrador',
            422,
            'Solo puedes eliminar una agrupación en borrador. Para eliminar una enviada o publicada, contacta al administrador.'
        );

        $agrupacion->delete();

        return response()->json(true);
    }

    // el representante cede la gestión (por ejemplo, si deja el grupo)
    public function transferirRepresentante(Request $request, Agrupacion $agrupacion, AgrupacionIntegrante $integrante)
    {
        $this->soloRepresentante($request, $agrupacion);
        $agrupacion->transferirRepresentante($integrante);
        $agrupacion->revisiones()->create([
            'accion' => 'enviado',
            'observacion' => "Transfirió la representación a {$integrante->nombre_completo}.",
            'user_id' => $request->user()->id,
        ]);

        return response()->json(true);
    }

    // ---------- helpers ----------

    private function miPersona(Request $request): Persona
    {
        $persona = $request->user()->persona;
        abort_unless($persona, 403, 'Necesitas una ficha de persona para gestionar agrupaciones.');

        return $persona;
    }

    private function soloRepresentante(Request $request, Agrupacion $agrupacion): void
    {
        abort_unless(
            $agrupacion->esRepresentante($this->miPersona($request)),
            403,
            'Solo el representante puede gestionar esta agrupación.'
        );
    }

    private function datos(StoreAgrupacionRequest $request): array
    {
        return [
            'nombre' => $request->input('nombre'),
            'codigo_comision' => $request->input('codigo_comision'),
            'descripcion' => Html::limpio($request->input('descripcion')),
            // se descartan las redes vacías para no guardar { "tiktok": null }
            'redes_sociales' => array_filter($request->input('redes_sociales', [])) ?: null,
        ];
    }

    // todo lo que necesita la pantalla de gestión, en una llamada
    private function detalle(Agrupacion $agrupacion): array
    {
        $agrupacion->load(['comision:codigo,nombre', 'logo', 'portada', 'actividades', 'integrantes.persona:id,slug', 'integrantes.persona.foto', 'revisiones.usuario:id,name']);

        return [
            ...$agrupacion->toArray(),
            'comision' => $agrupacion->comision?->nombre,
            'requisitos' => $agrupacion->requisitos(),
        ];
    }
}
