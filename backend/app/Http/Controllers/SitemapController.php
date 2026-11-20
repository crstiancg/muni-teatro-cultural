<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Comision;
use App\Models\Evento;
use App\Models\Persona;
use App\Support\FichaRegistro;
use App\Support\Seo;

// sitemap.xml, robots.txt y llms.txt: le dicen a Google (y a los buscadores
// con IA) qué indexar, qué no, y de qué trata el sitio
class SitemapController extends Controller
{
    public function sitemap()
    {
        $base = Seo::base();

        $urls = collect([
            ['loc' => "{$base}/", 'prioridad' => '1.0', 'frecuencia' => 'daily'],
            ['loc' => "{$base}/artistas", 'prioridad' => '0.9', 'frecuencia' => 'daily'],
            ['loc' => "{$base}/agrupaciones", 'prioridad' => '0.9', 'frecuencia' => 'weekly'],
            ['loc' => "{$base}/agenda", 'prioridad' => '0.9', 'frecuencia' => 'daily'],
            ['loc' => "{$base}/consejo", 'prioridad' => '0.6', 'frecuencia' => 'monthly'],
            ['loc' => "{$base}/privacidad", 'prioridad' => '0.2', 'frecuencia' => 'yearly'],
        ])
            ->merge(Persona::publicado()->get(['slug', 'updated_at'])->map(fn ($p) => [
                'loc' => "{$base}/artistas/{$p->slug}",
                'lastmod' => $p->updated_at?->toAtomString(),
                'prioridad' => '0.8',
                'frecuencia' => 'monthly',
            ]))
            ->merge(Agrupacion::publicado()->get(['slug', 'updated_at'])->map(fn ($a) => [
                'loc' => "{$base}/agrupaciones/{$a->slug}",
                'lastmod' => $a->updated_at?->toAtomString(),
                'prioridad' => '0.8',
                'frecuencia' => 'monthly',
            ]))
            // eventos publicados: los próximos importan más que los pasados
            ->merge(Evento::publicado()->get(['id', 'titulo', 'fecha_inicio', 'fecha_fin', 'updated_at'])->map(fn ($e) => [
                'loc' => "{$base}/agenda/{$e->ruta_publica}",
                'lastmod' => $e->updated_at?->toAtomString(),
                'prioridad' => ($e->fecha_fin ?? $e->fecha_inicio)->isFuture() ? '0.7' : '0.3',
                'frecuencia' => 'weekly',
            ]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . $urls->map(fn ($u) => '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                . (isset($u['lastmod']) ? "<lastmod>{$u['lastmod']}</lastmod>" : '')
                . "<changefreq>{$u['frecuencia']}</changefreq>"
                . "<priority>{$u['prioridad']}</priority></url>")->implode("\n")
            . "\n</urlset>\n";

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $base = Seo::base();
        $privadas = collect(config('seo.rutas_privadas'))->map(fn ($r) => "Disallow: /{$r}")->implode("\n");

        // El portal es una SPA: arma cada página con /api/publico/*. Si Google no
        // puede leerla, indexa el esqueleto sin artistas ni eventos, así que esa
        // parte de la API queda abierta (gana la regla más larga). El resto de la
        // API (panel), el buscador (combinaciones infinitas) y la verificación de
        // fichas (tokens) siguen cerrados.
        // /compartir/ redirige al perfil: indexarlo duplicaría cada página de artista
        $texto = "User-agent: *\nAllow: /\nAllow: /api/publico/\nDisallow: /api/\n"
            . "Disallow: /api/publico/buscar\nDisallow: /api/publico/verificar/\n"
            . "Disallow: /compartir/\n{$privadas}\n\nSitemap: {$base}/sitemap.xml\n";

        return response($texto, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    // llms.txt (llmstxt.org): resumen en Markdown para buscadores con IA
    // (ChatGPT, Gemini, Perplexity). Solo datos públicos, los mismos del portal.
    public function llms()
    {
        $base = Seo::base();
        $ciudad = config('seo.ciudad');
        $org = config('seo.organizacion');

        // "Música (Comisión especializada de música)": el nombre corto que usan el
        // portal y la ficha, con el oficial entre paréntesis para quien lo busque así
        $comisiones = Comision::where('tipo', 'grupo')->orderBy('codigo')->get()
            ->map(fn ($c) => '- ' . FichaRegistro::nombreComision($c)
                . ($c->nombre !== FichaRegistro::nombreComision($c) ? " ({$c->nombre})" : ''))
            ->implode("\n");
        $codigo = fn (int $n) => config('registro.prefijo') . str_pad((string) $n, config('registro.digitos'), '0', STR_PAD_LEFT);

        $texto = '# ' . config('seo.nombre_largo') . ' (' . config('seo.marca') . ")\n\n"
            . '> ' . config('seo.descripcion') . "\n\n"
            . "Registro oficial del {$org['nombre']}, a cargo de la {$org['madre']} ({$org['madre_url']}). "
            . "Reúne a los artistas, agrupaciones y agentes culturales de la provincia de {$ciudad}, Perú, "
            . "organizados en comisiones especializadas. Solo se publica lo que la municipalidad revisó y aprobó.\n\n"
            . "## Secciones\n\n"
            . "- [Artistas y agentes culturales]({$base}/artistas): directorio con perfil, disciplina y actividades de cada artista\n"
            . "- [Agrupaciones]({$base}/agrupaciones): conjuntos, bandas, comparsas y elencos\n"
            . "- [Agenda cultural]({$base}/agenda): eventos, conciertos y festividades en {$ciudad}\n"
            . "- [El Consejo]({$base}/consejo): qué es el Consejo Provincial de Cultura, sus gestiones, la Gerencia de Cultura y Turismo y sus resoluciones y ordenanzas\n"
            . "- [Política de privacidad]({$base}/privacidad)\n\n"
            . "## Comisiones especializadas\n\n{$comisiones}\n\n"
            . "## Código de registro y verificación\n\n"
            . "Cada agente cultural aprobado recibe un código correlativo ({$codigo(1)}, {$codigo(2)}...) "
            . "y una ficha de registro con un código QR. Al escanearlo se abre una página del portal que confirma "
            . "si la inscripción está vigente; el estado del sistema prevalece sobre el de la ficha impresa.\n\n"
            . "## Contacto\n\n"
            . "- {$org['direccion']}, {$org['localidad']}, Perú\n"
            . "- Teléfono: {$org['telefono']}\n"
            . "- Correo: {$org['correo']}\n\n"
            . "## Datos para máquinas\n\n"
            . "- [Sitemap]({$base}/sitemap.xml)\n";

        return response($texto, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
