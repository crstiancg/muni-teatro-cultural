<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;

// Página pública de una agrupación. Integrantes: nombre y rol, NUNCA el DNI
// (decisión del cliente). Los que son artistas publicados enlazan a su perfil.
class AgrupacionPublicaController extends Controller
{
    public function show(Agrupacion $agrupacion)
    {
        abort_unless($agrupacion->estado === 'aprobado' && $agrupacion->codigo_comision, 404);

        $agrupacion->load(['comision:codigo,cod_grupo,nombre', 'logo', 'integrantes.persona:id,slug,estado,codigo_comision']);

        return response()->json([
            'nombre' => $agrupacion->nombre,
            'slug' => $agrupacion->slug,
            'comision' => $agrupacion->comision?->nombre,
            'cod_grupo' => $agrupacion->comision?->cod_grupo,
            // HTML ya sanitizado al guardar (App\Support\Html::limpio)
            'descripcion' => $agrupacion->descripcion,
            'redes_sociales' => $agrupacion->redes_sociales ?? (object) [],
            'logo_url' => $agrupacion->logo?->url,
            'integrantes' => $agrupacion->integrantes->map(fn ($i) => [
                'nombre_completo' => $i->nombre_completo,
                'rol' => $i->rol,
                'es_representante' => $i->es_representante,
                // solo si el artista vinculado está publicado: si no, su perfil da 404
                'slug' => $i->persona?->estado === 'aprobado' && $i->persona?->codigo_comision
                    ? $i->persona->slug
                    : null,
            ]),
        ]);
    }
}
