<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Comision;
use App\Models\Persona;
use Illuminate\Http\Request;

// Endpoints SIN autenticación: solo exponen lo mínimo seguro para mostrar
// al público (nombre, comisión, actividades marcadas como públicas). Nunca
// DNI, correo, celular, dirección ni ningún otro dato personal sensible.
class PersonaPublicaController extends Controller
{
    public function index(Request $request)
    {
        $query = Persona::query()
            ->whereNotNull('codigo_comision')
            ->with(['comision:codigo,cod_grupo,cod_familia,nombre']);

        if ($request->filled('grupo')) {
            $query->whereHas('comision', fn ($q) => $q->where('cod_grupo', $request->string('grupo')));
        }

        if ($request->filled('buscar')) {
            $query->where('nombre_completo', 'like', '%' . $request->string('buscar') . '%');
        }

        $personas = $query->orderBy('nombre_completo')->get();

        return response()->json($personas->map(fn (Persona $persona) => $this->datosPublicos($persona)));
    }

    public function show(Persona $persona)
    {
        abort_unless($persona->codigo_comision, 404);

        $persona->load([
            'comision:codigo,cod_grupo,cod_familia,nombre',
            'comisionAlternativo:codigo,cod_grupo,cod_familia,nombre',
            'actividades' => fn ($q) => $q->where('flag_activo', true)->where('flag_publico', true),
        ]);

        return response()->json([
            ...$this->datosPublicos($persona),
            ...$this->datosContacto($persona),
            'comision_alternativo' => $persona->comisionAlternativo?->nombre,
            'actividades' => $persona->actividades->map(fn ($a) => [
                'id' => $a->id,
                'descripcion' => $a->descripcion,
                'imagen_url' => $a->imagen_url,
            ]),
        ]);
    }

    // ÚNICO lugar donde se decide qué datos de contacto salen al público.
    // Decisión del cliente (18/09/2026): se publica también el DNI, aun habiendo
    // advertido el riesgo de suplantación y la Ley 29733. Para dejar de exponer
    // un campo, basta con sacarlo de este arreglo.
    private function datosContacto(Persona $persona): array
    {
        return [
            'dni' => $persona->dni,
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
            ->whereHas('persona', fn ($q) => $q->whereNotNull('codigo_comision'))
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

    public function grupos()
    {
        return response()->json(
            Comision::where('tipo', 'grupo')->orderBy('nombre')->get(['cod_grupo', 'nombre'])
        );
    }

    // todo lo que necesita la portada en una sola llamada: conteos reales,
    // comisiones con su imagen representativa, y las últimas actividades
    public function portada()
    {
        $actividadesPorGrupo = Actividad::query()
            ->where('actividades.flag_activo', true)
            ->where('actividades.flag_publico', true)
            ->join('personas', 'actividades.persona_id', '=', 'personas.id')
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            ->select('actividades.imagen_path', 'comisions.cod_grupo')
            ->get()
            ->groupBy('cod_grupo');

        $consejerosPorGrupo = Persona::query()
            ->whereNotNull('personas.codigo_comision')
            ->join('comisions', 'personas.codigo_comision', '=', 'comisions.codigo')
            ->groupBy('comisions.cod_grupo')
            ->selectRaw('comisions.cod_grupo as cod_grupo, count(*) as total')
            ->pluck('total', 'cod_grupo');

        $grupos = Comision::where('tipo', 'grupo')
            ->orderBy('nombre')
            ->get(['cod_grupo', 'nombre'])
            ->map(function (Comision $grupo) use ($actividadesPorGrupo, $consejerosPorGrupo) {
                $imagen = $actividadesPorGrupo->get($grupo->cod_grupo)?->first()?->imagen_path;

                return [
                    'cod_grupo' => $grupo->cod_grupo,
                    'nombre' => $grupo->nombre,
                    'consejeros' => (int) ($consejerosPorGrupo[$grupo->cod_grupo] ?? 0),
                    'imagen_url' => $imagen ? asset('storage/' . $imagen) : null,
                ];
            });

        return response()->json([
            'stats' => [
                'consejeros' => Persona::whereNotNull('codigo_comision')->count(),
                'comisiones' => Comision::where('tipo', 'familia')->count(),
                'grupos' => $grupos->count(),
                'actividades' => Actividad::where('flag_activo', true)->where('flag_publico', true)->count(),
            ],
            'grupos' => $grupos,
            'destacadas' => $this->actividadesDestacadas()->getData(true),
        ]);
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
        ];
    }
}
