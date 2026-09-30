<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Comision;
use App\Models\Persona;
use App\Support\UserAgent;
use Illuminate\Http\Request;

// Endpoints SIN autenticación: solo exponen lo mínimo seguro para mostrar
// al público (nombre, comisión, actividades marcadas como públicas). Nunca
// DNI, correo, celular, dirección ni ningún otro dato personal sensible.
class PersonaPublicaController extends Controller
{
    public function index(Request $request)
    {
        $query = Persona::query()
            ->publicado()
            ->with([
                'comision:codigo,cod_grupo,cod_familia,nombre',
                'foto',
                // una sola actividad por persona: la que se usa como foto de la
                // tarjeta en el directorio
                'actividades' => fn ($q) => $q->where('flag_activo', true)
                    ->where('flag_publico', true)
                    ->latest()
                    ->limit(1),
            ])
            ->withCount([
                'actividades as actividades_count' => fn ($q) => $q->where('flag_activo', true)
                    ->where('flag_publico', true),
            ]);

        if ($request->filled('grupo')) {
            $query->whereHas('comision', fn ($q) => $q->where('cod_grupo', $request->string('grupo')));
        }

        if ($request->filled('buscar')) {
            $termino = '%' . $request->string('buscar') . '%';
            $query->where(function ($q) use ($termino) {
                $q->where('nombre_completo', 'like', $termino)
                    ->orWhereHas('comision', fn ($c) => $c->where('nombre', 'like', $termino));
            });
        }

        // el registro puede tener cientos de artistas: se pagina para no mandar
        // la tabla entera en cada carga del directorio
        $porPagina = min((int) $request->input('por_pagina', 24), 60);
        $personas = $query->orderBy('nombre_completo')->paginate($porPagina);

        return response()->json([
            'data' => collect($personas->items())
                ->map(fn (Persona $persona) => $this->datosPublicos($persona))
                ->all(),
            'meta' => [
                'pagina' => $personas->currentPage(),
                'ultima_pagina' => $personas->lastPage(),
                'total' => $personas->total(),
                'por_pagina' => $personas->perPage(),
            ],
        ]);
    }

    public function show(Request $request, Persona $persona)
    {
        abort_unless($persona->estado === 'aprobado' && $persona->codigo_comision, 404);

        if (! UserAgent::esBot($request->userAgent())) {
            $persona->registrarVisita($request->ip() . '|' . $request->userAgent());
        }

        $persona->load([
            'comision:codigo,cod_grupo,cod_familia,nombre',
            'comisionAlternativo:codigo,cod_grupo,cod_familia,nombre',
            'foto',
            'actividades' => fn ($q) => $q->where('flag_activo', true)->where('flag_publico', true),
        ]);

        return response()->json([
            ...$this->datosPublicos($persona),
            ...$this->datosContacto($persona),
            'comision_alternativo' => $persona->comisionAlternativo?->nombre,
            // HTML ya sanitizado al guardar (Persona::biografiaLimpia)
            'biografia' => $persona->biografia,
            'redes_sociales' => $persona->redes_sociales ?? (object) [],
            'actividades' => $persona->actividades->map(fn ($a) => [
                'id' => $a->id,
                'descripcion' => $a->descripcion,
                'imagen_url' => $a->imagen_url,
            ]),
        ]);
    }

    // ÚNICO lugar donde se decide qué datos de contacto salen al público.
    // El DNI NO se publica (decisión 30/09/2026): además de la Ley 29733, es la
    // contraseña inicial de cada artista. Para dejar de exponer un campo, basta
    // con sacarlo de este arreglo.
    private function datosContacto(Persona $persona): array
    {
        return [
            'celular' => $persona->celular,
            'correo' => $persona->correo,
        ];
    }

    // últimas actividades públicas de CUALQUIER consejero, para destacar en portada
    public function actividadesDestacadas()
    {
        $actividades = Actividad::query()
            ->where('flag_activo', true)
            ->where('flag_publico', true)
            ->whereHas('persona', fn ($q) => $q->publicado())
            ->with('persona:id,slug,nombre_completo')
            ->latest()
            ->limit(8)
            ->get();

        return response()->json($actividades->map(fn (Actividad $a) => [
            'id' => $a->id,
            'descripcion' => $a->descripcion,
            'imagen_url' => $a->imagen_url,
            'persona_slug' => $a->persona->slug,
            'persona_nombre' => $a->persona->nombre_completo,
        ]));
    }

    // cada grupo con cuántos artistas tiene: el directorio muestra el número al
    // lado de cada filtro, así se sabe qué hay detrás antes de hacer clic
    public function grupos()
    {
        $porGrupo = Persona::query()
            ->publicado()
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            ->groupBy('comisions.cod_grupo')
            ->selectRaw('comisions.cod_grupo as cod_grupo, count(*) as total')
            ->pluck('total', 'cod_grupo');

        $grupos = Comision::where('tipo', 'grupo')
            ->orderBy('nombre')
            ->get(['cod_grupo', 'nombre'])
            ->map(fn (Comision $grupo) => [
                'cod_grupo' => $grupo->cod_grupo,
                'nombre' => $grupo->nombre,
                'consejeros' => (int) ($porGrupo[$grupo->cod_grupo] ?? 0),
            ]);

        return response()->json($grupos);
    }

    // todo lo que necesita la portada en una sola llamada: conteos reales,
    // comisiones con su imagen representativa, y las últimas actividades
    public function portada()
    {
        $actividadesPorGrupo = Actividad::query()
            ->where('actividades.flag_activo', true)
            ->where('actividades.flag_publico', true)
            ->join('personas', 'actividades.persona_id', '=', 'personas.id')
            ->where('personas.estado', 'aprobado')
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            // el id alcanza: la imagen llega por el eager load de "adjunto" (Actividad::$with)
            ->select('actividades.id', 'comisions.cod_grupo')
            ->get()
            ->groupBy('cod_grupo');

        $consejerosPorGrupo = Persona::query()
            ->publicado()
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            ->groupBy('comisions.cod_grupo')
            ->selectRaw('comisions.cod_grupo as cod_grupo, count(*) as total')
            ->pluck('total', 'cod_grupo');

        $grupos = Comision::where('tipo', 'grupo')
            ->orderBy('nombre')
            ->get(['cod_grupo', 'nombre'])
            ->map(function (Comision $grupo) use ($actividadesPorGrupo, $consejerosPorGrupo) {

                return [
                    'cod_grupo' => $grupo->cod_grupo,
                    'nombre' => $grupo->nombre,
                    'consejeros' => (int) ($consejerosPorGrupo[$grupo->cod_grupo] ?? 0),
                    'imagen_url' => $actividadesPorGrupo->get($grupo->cod_grupo)?->first()?->imagen_miniatura_url,
                ];
            });

        return response()->json([
            'stats' => [
                'consejeros' => Persona::publicado()->count(),
                'comisiones' => Comision::where('tipo', 'familia')->count(),
                'grupos' => $grupos->count(),
                'actividades' => Actividad::where('flag_activo', true)->where('flag_publico', true)
                    ->whereHas('persona', fn ($q) => $q->publicado())->count(),
            ],
            'grupos' => $grupos,
            'destacadas' => $this->actividadesDestacadas()->getData(true),
            'artistas' => $this->artistasDestacados(),
        ]);
    }

    // artistas con trabajo publicado, para presentarlos en portada con su foto.
    // Se toma la ultima actividad publica de cada uno como imagen de portada y
    // las siguientes como miniaturas de su trabajo.
    private function artistasDestacados(int $limite = 8): array
    {
        return Persona::query()
            ->publicado()
            ->whereHas('actividades', fn ($q) => $q->where('flag_activo', true)->where('flag_publico', true))
            ->with([
                'comision:codigo,cod_grupo,cod_familia,nombre',
                'foto',
                'actividades' => fn ($q) => $q->where('flag_activo', true)
                    ->where('flag_publico', true)
                    ->latest()
                    ->limit(4),
            ])
            ->inRandomOrder()
            ->limit($limite)
            ->get()
            ->map(fn (Persona $persona) => [
                ...$this->datosPublicos($persona),
                'imagen_url' => $persona->actividades->first()?->imagen_miniatura_url,
                'miniaturas' => $persona->actividades->skip(1)->take(3)
                    ->pluck('imagen_miniatura_url')->values(),
                'total_actividades' => $persona->actividades_count
                    ?? $persona->actividades->count(),
            ])
            ->all();
    }

    private function datosPublicos(Persona $persona): array
    {
        return [
            'id' => $persona->id,
            'slug' => $persona->slug,
            'nombre' => $persona->nombre,
            'apellidos' => trim("{$persona->apellido_paterno} {$persona->apellido_materno}"),
            'nombre_completo' => $persona->nombre_completo,
            'comision' => $persona->comision?->nombre,
            'cod_grupo' => $persona->comision?->cod_grupo,
            // el directorio se presenta con fotos, así que cada persona viaja
            // con su imagen de portada y cuántas actividades tiene publicadas
            'imagen_url' => $persona->relationLoaded('actividades')
                ? $persona->actividades->first()?->imagen_miniatura_url
                : null,
            'total_actividades' => $persona->actividades_count ?? null,
            // foto de perfil (tabla archivos); distinta de imagen_url, que es la
            // portada tomada de la última actividad
            // miniatura: se usa en tarjetas y avatares, nunca a pantalla completa
            'foto_url' => $persona->relationLoaded('foto') ? $persona->foto?->miniatura_url : null,
        ];
    }
}
