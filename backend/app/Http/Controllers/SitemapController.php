<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Persona;

// sitemap.xml y robots.txt: le dicen a Google qué indexar y qué no
class SitemapController extends Controller
{
    public function sitemap()
    {
        $base = config('app.frontend_url');

        $urls = collect([
            ['loc' => "{$base}/", 'prioridad' => '1.0'],
            ['loc' => "{$base}/consejeros", 'prioridad' => '0.9'],
            ['loc' => "{$base}/agrupaciones", 'prioridad' => '0.9'],
        ])
            ->merge(Persona::publicado()->get(['slug', 'updated_at'])->map(fn ($p) => [
                'loc' => "{$base}/consejeros/{$p->slug}",
                'lastmod' => $p->updated_at?->toAtomString(),
                'prioridad' => '0.8',
            ]))
            ->merge(Agrupacion::publicado()->get(['slug', 'updated_at'])->map(fn ($a) => [
                'loc' => "{$base}/agrupaciones/{$a->slug}",
                'lastmod' => $a->updated_at?->toAtomString(),
                'prioridad' => '0.8',
            ]));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . $urls->map(fn ($u) => '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                . (isset($u['lastmod']) ? "<lastmod>{$u['lastmod']}</lastmod>" : '')
                . "<priority>{$u['prioridad']}</priority></url>")->implode("\n")
            . "\n</urlset>\n";

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots()
    {
        $base = config('app.frontend_url');
        $privadas = collect(config('seo.rutas_privadas'))->map(fn ($r) => "Disallow: /{$r}")->implode("\n");

        $texto = "User-agent: *\nAllow: /\nDisallow: /api/\n{$privadas}\n\nSitemap: {$base}/sitemap.xml\n";

        return response($texto, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
