<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Comision;
use App\Models\Evento;
use App\Models\Persona;
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
            ['loc' => "{$base}/consejeros", 'prioridad' => '0.9', 'frecuencia' => 'daily'],
            ['loc' => "{$base}/agrupaciones", 'prioridad' => '0.9', 'frecuencia' => 'weekly'],
            ['loc' => "{$base}/agenda", 'prioridad' => '0.9', 'frecuencia' => 'daily'],
            ['loc' => "{$base}/privacidad", 'prioridad' => '0.2', 'frecuencia' => 'yearly'],
        ])
            ->merge(Persona::publicado()->get(['slug', 'updated_at'])->map(fn ($p) => [
                'loc' => "{$base}/consejeros/{$p->slug}",
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

        // /compartir/ redirige al perfil: indexarlo duplicaría cada página de artista
        $texto = "User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /compartir/\n{$privadas}\n\nSitemap: {$base}/sitemap.xml\n";

        return response($texto, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    // llms.txt (llmstxt.org): resumen en Markdown para buscadores con IA
    // (ChatGPT, Gemini, Perplexity). Solo datos públicos, los mismos del portal.
    public function llms()
    {
        $base = Seo::base();
        $ciudad = config('seo.ciudad');
        $org = config('seo.organizacion');

        $comisiones = Comision::where('tipo', 'grupo')->orderBy('codigo')->pluck('nombre')
            ->map(fn ($n) => "- {$n}")->implode("\n");

        $texto = '# ' . config('seo.nombre_largo') . ' (' . config('seo.marca') . ")\n\n"
            . '> ' . config('seo.descripcion') . "\n\n"
            . "Registro oficial del {$org['nombre']}, a cargo de la {$org['madre']} ({$org['madre_url']}). "
            . "Reúne a los artistas, agrupaciones y agentes culturales de la provincia de {$ciudad}, Perú, "
            . "organizados en comisiones especializadas. Solo se publica lo que la municipalidad revisó y aprobó.\n\n"
            . "## Secciones\n\n"
            . "- [Artistas y agentes culturales]({$base}/consejeros): directorio con perfil, disciplina y actividades de cada artista\n"
            . "- [Agrupaciones]({$base}/agrupaciones): conjuntos, bandas, comparsas y elencos\n"
            . "- [Agenda cultural]({$base}/agenda): eventos, conciertos y festividades en {$ciudad}\n"
            . "- [Política de privacidad]({$base}/privacidad)\n\n"
            . "## Comisiones especializadas\n\n{$comisiones}\n\n"
            . "## Contacto\n\n"
            . "- {$org['direccion']}, {$org['localidad']}, Perú\n"
            . "- Teléfono: {$org['telefono']}\n"
            . "- Correo: {$org['correo']}\n\n"
            . "## Datos para máquinas\n\n"
            . "- [Sitemap]({$base}/sitemap.xml)\n";

        return response($texto, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
