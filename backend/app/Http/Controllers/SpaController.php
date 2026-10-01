<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Persona;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Sirve la SPA (build de Quasar) para cualquier URL que no sea de la API, con el
// <head> de cada página pública ya escrito. Sin esto, Google y los buscadores
// que no ejecutan JavaScript ven un HTML vacío con el mismo título en todo el sitio.
class SpaController extends Controller
{
    public function __invoke(Request $request)
    {
        // una ruta de la API que no existe es un 404 JSON, nunca la SPA
        abort_if($request->is('api/*'), 404);

        $plantilla = config('seo.spa_index');
        abort_unless(is_file($plantilla), 500, 'Falta el build del front: corre "php artisan spa:publicar".');

        [$meta, $estado] = $this->metaDe($request);

        return response($this->inyectar(file_get_contents($plantilla), $meta), $estado)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    // ---------- qué dice cada página ----------

    private function metaDe(Request $request): array
    {
        $segmentos = $request->segments();
        $base = $this->base();
        $sitio = config('seo.sitio');

        // panel y login: se sirven, pero no se indexan
        if (in_array($segmentos[0] ?? '', config('seo.rutas_privadas'))) {
            return [['titulo' => $sitio, 'robots' => 'noindex, nofollow'], 200];
        }

        $ruta = implode('/', $segmentos);

        return match (true) {
            $ruta === '' => [[
                'titulo' => "{$sitio} · " . config('seo.ciudad'),
                'descripcion' => config('seo.descripcion'),
                'canonica' => "{$base}/",
                'jsonld' => [[
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $sitio,
                    'url' => "{$base}/",
                ]],
            ], 200],
            $ruta === 'consejeros' => [[
                'titulo' => "Artistas y agentes culturales · {$sitio}",
                'descripcion' => 'Directorio de artistas y agentes culturales de ' . config('seo.ciudad') . ': música, danza, teatro, artes plásticas y más.',
                'canonica' => "{$base}/consejeros",
            ], 200],
            $ruta === 'agrupaciones' => [[
                'titulo' => "Agrupaciones · {$sitio}",
                'descripcion' => 'Conjuntos, comparsas y elencos que mantienen viva la cultura de ' . config('seo.ciudad') . '.',
                'canonica' => "{$base}/agrupaciones",
            ], 200],
            count($segmentos) >= 2 && $segmentos[0] === 'consejeros' => $this->metaPersona($segmentos[1], $segmentos[2] ?? null),
            count($segmentos) === 2 && $segmentos[0] === 'agrupaciones' => $this->metaAgrupacion($segmentos[1]),
            // cualquier otra URL: la SPA muestra su "no encontrado" y el estado es 404
            default => [$this->noEncontrado(), 404],
        };
    }

    private function metaPersona(string $slug, ?string $subpagina): array
    {
        $persona = Persona::publicado()
            ->where('slug', $slug)
            ->with(['comision:codigo,nombre', 'foto'])
            ->first();

        if (! $persona || ($subpagina && $subpagina !== 'galeria')) {
            return [$this->noEncontrado(), 404];
        }

        $base = $this->base();
        $url = "{$base}/consejeros/{$persona->slug}";
        $comision = $persona->comision?->nombre;
        $portada = $persona->actividades()->where('flag_activo', true)->where('flag_publico', true)->latest()->first();
        $agrupaciones = $persona->agrupaciones()->publicado()->get(['agrupaciones.nombre', 'agrupaciones.slug']);

        $descripcion = Str::limit(Html::texto($persona->biografia), 160)
            ?: "{$persona->nombre_completo}, de {$comision}, en el registro cultural de " . config('seo.ciudad') . '.';

        return [[
            'titulo' => ($subpagina ? 'Galería de ' : '') . "{$persona->nombre_completo} · {$comision} | " . config('seo.sitio'),
            'descripcion' => $descripcion,
            // la galería es una vista del mismo perfil: una sola URL canónica
            'canonica' => $url,
            'imagen' => $persona->foto?->url ?? $portada?->imagen_url,
            'tipo_og' => 'profile',
            'jsonld' => [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Person',
                    'name' => $persona->nombre_completo,
                    'url' => $url,
                    'image' => $persona->foto?->url,
                    'description' => $descripcion,
                    'knowsAbout' => $comision,
                    'homeLocation' => ['@type' => 'Place', 'name' => config('seo.ciudad')],
                    'sameAs' => array_values($persona->redes_sociales ?? []) ?: null,
                    'memberOf' => $agrupaciones->map(fn ($a) => [
                        '@type' => 'PerformingGroup',
                        'name' => $a->nombre,
                        'url' => "{$base}/agrupaciones/{$a->slug}",
                    ])->all() ?: null,
                ]),
                $this->migas([['Artistas', "{$base}/consejeros"], [$persona->nombre_completo, $url]]),
            ],
        ], 200];
    }

    private function metaAgrupacion(string $slug): array
    {
        $agrupacion = Agrupacion::publicado()
            ->where('slug', $slug)
            ->with(['comision:codigo,nombre', 'logo', 'portada', 'integrantes'])
            ->first();

        if (! $agrupacion) {
            return [$this->noEncontrado(), 404];
        }

        $base = $this->base();
        $url = "{$base}/agrupaciones/{$agrupacion->slug}";
        $comision = $agrupacion->comision?->nombre;
        $descripcion = Str::limit(Html::texto($agrupacion->descripcion), 160)
            ?: "{$agrupacion->nombre}, agrupación de {$comision} en " . config('seo.ciudad') . '.';

        return [[
            'titulo' => "{$agrupacion->nombre} · {$comision} | " . config('seo.sitio'),
            'descripcion' => $descripcion,
            'canonica' => $url,
            'imagen' => $agrupacion->portada?->url ?? $agrupacion->logo?->url,
            'jsonld' => [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'PerformingGroup',
                    'name' => $agrupacion->nombre,
                    'url' => $url,
                    'logo' => $agrupacion->logo?->url,
                    'image' => $agrupacion->portada?->url,
                    'description' => $descripcion,
                    'sameAs' => array_values($agrupacion->redes_sociales ?? []) ?: null,
                    // solo nombres: el DNI de los integrantes nunca sale al público
                    'member' => $agrupacion->integrantes
                        ->map(fn ($i) => ['@type' => 'Person', 'name' => $i->nombre_completo])
                        ->all(),
                ]),
                $this->migas([['Agrupaciones', "{$base}/agrupaciones"], [$agrupacion->nombre, $url]]),
            ],
        ], 200];
    }

    private function noEncontrado(): array
    {
        return ['titulo' => 'Página no encontrada · ' . config('seo.sitio'), 'robots' => 'noindex'];
    }

    private function migas(array $pasos): array
    {
        $items = [['Inicio', $this->base() . '/'], ...$pasos];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn ($paso, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $paso[0],
                'item' => $paso[1],
            ])->all(),
        ];
    }

    private function base(): string
    {
        return config('app.frontend_url');
    }

    // ---------- armado del <head> ----------

    private function inyectar(string $html, array $meta): string
    {
        $e = fn (?string $texto) => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descripcion = $meta['descripcion'] ?? config('seo.descripcion');

        $etiquetas = array_filter([
            '<meta name="description" content="' . $e($descripcion) . '">',
            isset($meta['robots']) ? '<meta name="robots" content="' . $e($meta['robots']) . '">' : null,
            isset($meta['canonica']) ? '<link rel="canonical" href="' . $e($meta['canonica']) . '">' : null,
            '<meta property="og:site_name" content="' . $e(config('seo.sitio')) . '">',
            '<meta property="og:locale" content="es_PE">',
            '<meta property="og:type" content="' . $e($meta['tipo_og'] ?? 'website') . '">',
            '<meta property="og:title" content="' . $e($meta['titulo']) . '">',
            '<meta property="og:description" content="' . $e($descripcion) . '">',
            isset($meta['canonica']) ? '<meta property="og:url" content="' . $e($meta['canonica']) . '">' : null,
            isset($meta['imagen']) ? '<meta property="og:image" content="' . $e($meta['imagen']) . '">' : null,
            '<meta name="twitter:card" content="' . (isset($meta['imagen']) ? 'summary_large_image' : 'summary') . '">',
        ]);

        // JSON_HEX_TAG: un "</script>" escrito por un usuario no puede cerrar el bloque
        foreach ($meta['jsonld'] ?? [] as $dato) {
            $etiquetas[] = '<script type="application/ld+json">'
                . json_encode($dato, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
                . '</script>';
        }

        // fuera la descripción genérica del build; el título se reemplaza
        $html = preg_replace('/<meta\s+name="description"[^>]*>\s*/i', '', $html);
        $html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $e($meta['titulo']) . '</title>', $html, 1);

        return str_replace('</head>', '    ' . implode("\n    ", $etiquetas) . "\n  </head>", $html);
    }
}
