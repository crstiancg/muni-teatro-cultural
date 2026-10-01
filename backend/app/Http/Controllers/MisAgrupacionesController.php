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
            ->with(['comision:codigo,nombre', 'logo'])
            ->orderBy('nombre')
            ->get()
            ->map(fn (Agrupacion $a) => [
                ...$a->only(['id', 'nombre', 'slug', 'estado', 'codigo_comision']),
                'comision' => $a->comision?->nombre,
                'logo' => $a->logo,
                'rol' => $a->pivot->rol,
                'es_representante' => (bool) $a->pivot->es_representante,
            ]);

        return response()->json($agrupaciones);
    }

    public function show(Request $request, Agrupacion $agrupacion)
    {
        $this->soloRepresentante($request, $agrupacion);

        return response()->json($this->detalle($agrupacion));
    }

    public function store(StoreAgrupacionRequest $request)
    {
        $persona = $this->miPersona($request);

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

        // igual que los perfiles: publicada, el cambio se ve al toque y el admin recibe aviso
        if ($agrupacion->estado === 'aprobado' && $agrupacion->wasChanged(['nombre', 'codigo_comision', 'descripcion', 'redes_sociales'])) {
            $agrupacion->notificarAdmins('agrupacion_actualizacion', "La agrupación {$agrupacion->nombre} actualizó sus datos.");
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

        return response()->json($integrante->load('persona:id,slug'));
    }

    public function destroyIntegrante(Request $request, Agrupacion $agrupacion, AgrupacionIntegrante $integrante)
    {
        $this->soloRepresentante($request, $agrupacion);
        abort_unless($integrante->agrupacion_id === $agrupacion->id, 404);
        // sin representante nadie podría gestionarla
        abort_if($integrante->es_representante, 422, 'No se puede quitar al representante de la agrupación.');

        $integrante->delete();

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
        $agrupacion->load(['comision:codigo,nombre', 'logo', 'integrantes.persona:id,slug', 'revisiones.usuario:id,name']);

        return [
            ...$agrupacion->toArray(),
            'comision' => $agrupacion->comision?->nombre,
            'requisitos' => $agrupacion->requisitos(),
        ];
    }
}
