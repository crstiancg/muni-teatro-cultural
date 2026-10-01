<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use Illuminate\Http\Request;

// Portal: directorio y página de cada agrupación. Integrantes: nombre, rol y
// foto (si es artista registrado), NUNCA el DNI (decisión del cliente).
class AgrupacionPublicaController extends Controller
{
    public function index(Request $request)
    {
        $query = Agrupacion::query()
            ->publicado()
            ->with(['comision:codigo,cod_grupo,nombre', 'logo', 'portada'])
            ->withCount('integrantes')
            ->orderBy('nombre');

        if ($request->filled('grupo')) {
            $query->whereHas('comision', fn ($q) => $q->where('cod_grupo', $request->string('grupo')));
        }

        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', '%' . $request->string('buscar') . '%');
        }

        $agrupaciones = $query->paginate(min((int) $request->input('por_pagina', 12), 48));

        return response()->json([
            'data' => collect($agrupaciones->items())->map(fn (Agrupacion $a) => [
                'nombre' => $a->nombre,
                'slug' => $a->slug,
                'comision' => $a->comision?->nombre,
                'cod_grupo' => $a->comision?->cod_grupo,
                // miniaturas: el directorio muestra muchas tarjetas
                'logo_url' => $a->logo?->miniatura_url,
                'portada_url' => $a->portada?->miniatura_url,
                'total_integrantes' => $a->integrantes_count,
            ]),
            'meta' => [
                'pagina' => $agrupaciones->currentPage(),
                'ultima_pagina' => $agrupaciones->lastPage(),
                'total' => $agrupaciones->total(),
            ],
        ]);
    }

    public function show(Agrupacion $agrupacion)
    {
        abort_unless($agrupacion->estado === 'aprobado' && $agrupacion->codigo_comision, 404);

        $agrupacion->load([
            'comision:codigo,cod_grupo,nombre',
            'logo',
            'portada',
            'integrantes.persona:id,slug,estado,codigo_comision',
            'integrantes.persona.foto',
            'actividades' => fn ($q) => $q->where('flag_activo', true)->where('flag_publico', true),
        ]);

        return response()->json([
            'nombre' => $agrupacion->nombre,
            'slug' => $agrupacion->slug,
            'comision' => $agrupacion->comision?->nombre,
            'cod_grupo' => $agrupacion->comision?->cod_grupo,
            // HTML ya sanitizado al guardar (App\Support\Html::limpio)
            'descripcion' => $agrupacion->descripcion,
            'redes_sociales' => $agrupacion->redes_sociales ?? (object) [],
            'logo_url' => $agrupacion->logo?->url,
            'portada_url' => $agrupacion->portada?->url,
            'integrantes' => $agrupacion->integrantes->map(function ($i) {
                $publicado = $i->persona?->estado === 'aprobado' && $i->persona?->codigo_comision;

                return [
                    'nombre_completo' => $i->nombre_completo,
                    'rol' => $i->rol,
                    'es_representante' => $i->es_representante,
                    // solo artistas publicados: si no, su perfil da 404
                    'slug' => $publicado ? $i->persona->slug : null,
                    'foto_url' => $publicado ? $i->persona->foto?->miniatura_url : null,
                ];
            }),
            'actividades' => $agrupacion->actividades->map(fn ($a) => [
                'id' => $a->id,
                'titulo' => $a->titulo,
                'descripcion' => $a->descripcion,
                'descripcion_texto' => $a->descripcion_texto,
                'imagen_url' => $a->imagen_url,
                'imagen_miniatura_url' => $a->imagen_miniatura_url,
            ]),
        ]);
    }
}
