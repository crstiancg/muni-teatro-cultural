<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Link para compartir un perfil: /compartir/{slug}.
// Los bots de vista previa (WhatsApp, Facebook, ...) no ejecutan JavaScript, así
// que la SPA no les sirve: a ellos se les devuelve un HTML con Open Graph. A una
// persona se la redirige directo al perfil del portal.
class CompartirPerfilController extends Controller
{
    public function __invoke(Request $request, Persona $persona)
    {
        $urlPerfil = config('app.frontend_url') . '/consejeros/' . $persona->slug;

        // un perfil no publicado no se previsualiza: se manda al portal
        if ($persona->estado !== 'aprobado' || ! $persona->codigo_comision) {
            return redirect()->away(config('app.frontend_url') . '/consejeros');
        }

        if (! UserAgent::esBot($request->userAgent())) {
            return redirect()->away($urlPerfil);
        }

        $persona->load(['comision:codigo,nombre', 'foto']);
        $portada = $persona->actividades()
            ->where('flag_activo', true)
            ->where('flag_publico', true)
            ->latest()
            ->first();

        $descripcion = $persona->biografia
            ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($persona->biografia))), 180)
            : "Integra {$persona->comision?->nombre} del registro cultural.";

        return response()->view('compartir', [
            'titulo' => $persona->nombre_completo,
            'descripcion' => $descripcion,
            // la original (no la miniatura): WhatsApp y Facebook piden al menos 600 px
            'imagen' => $persona->foto?->url ?? $portada?->imagen_url,
            'urlCompartir' => $request->url(),
            'urlPerfil' => $urlPerfil,
        ]);
    }
}
