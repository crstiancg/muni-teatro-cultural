<?php

namespace App\Http\Controllers;

use App\Models\Agrupacion;
use App\Models\Evento;
use App\Models\Persona;
use App\Support\Html;
use App\Support\Seo;
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
        $marca = config('seo.marca');
        $ciudad = config('seo.ciudad');

        // panel y login: se sirven, pero no se indexan
        if (in_array($segmentos[0] ?? '', config('seo.rutas_privadas'))) {
            return [['titulo' => $marca, 'robots' => 'noindex, nofollow'], 200];
        }

        $ruta = implode('/', $segmentos);

        return match (true) {
            // portada: la entidad completa (organización + sitio web con buscador)
            $ruta === '' => [[
                'titulo' => config('seo.nombre_largo') . " | {$marca}",
                'descripcion' => config('seo.descripcion'),
                'canonica' => "{$base}/",
                'jsonld' => [[
                    '@context' => 'https://schema.org',
                    '@graph' => [Seo::organizacion(), Seo::sitio()],
                ]],
            ], 200],
            $ruta === 'consejeros' => [[
                'titulo' => "Artistas y agentes culturales de {$ciudad} | {$marca}",
                'descripcion' => "Directorio de artistas y agentes culturales de {$ciudad}: músicos, danzantes, sikuris, bordadores, teatro, artes plásticas y más.",
                'canonica' => "{$base}/consejeros",
                'jsonld' => [$this->coleccion("Artistas y agentes culturales de {$ciudad}", "{$base}/consejeros")],
            ], 200],
            $ruta === 'privacidad' => [[
                'titulo' => "Política de privacidad | {$marca}",
                'descripcion' => 'Cómo tratamos los datos personales de artistas, agentes culturales e integrantes de agrupaciones (Ley N.° 29733).',
                'canonica' => "{$base}/privacidad",
            ], 200],
            $ruta === 'agrupaciones' => [[
                'titulo' => "Agrupaciones culturales de {$ciudad} | {$marca}",
                'descripcion' => "Conjuntos de sikuris, bandas, comparsas y elencos que mantienen viva la cultura de {$ciudad}.",
                'canonica' => "{$base}/agrupaciones",
                'jsonld' => [$this->coleccion("Agrupaciones culturales de {$ciudad}", "{$base}/agrupaciones")],
            ], 200],
            $ruta === 'agenda' => [[
                'titulo' => "Agenda cultural de {$ciudad}: eventos, conciertos y festivales | {$marca}",
                'descripcion' => "Funciones, conciertos, ferias, talleres y festividades en {$ciudad}: el calendario cultural de la ciudad.",
                'canonica' => "{$base}/agenda",
                'jsonld' => [$this->coleccion("Agenda cultural de {$ciudad}", "{$base}/agenda")],
            ], 200],
            count($segmentos) >= 2 && $segmentos[0] === 'consejeros' => $this->metaPersona($segmentos[1], $segmentos[2] ?? null),
            count($segmentos) === 2 && $segmentos[0] === 'agrupaciones' => $this->metaAgrupacion($segmentos[1]),
            count($segmentos) === 2 && $segmentos[0] === 'agenda' => $this->metaEvento($segmentos[1]),
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

        $ciudad = config('seo.ciudad');
        $descripcion = Str::limit(Html::texto($persona->biografia), 160)
            ?: "{$persona->nombre_completo}: {$comision} en {$ciudad}. Perfil, trayectoria y actividades en el Registro de Agentes Culturales de {$ciudad}.";

        return [[
            // "Nombre · Familia en Puno | RACC Puno": nombre + disciplina + ciudad es lo que se busca
            'titulo' => ($subpagina ? 'Galería de ' : '') . "{$persona->nombre_completo} · {$comision} en {$ciudad} | " . config('seo.marca'),
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
                    'jobTitle' => $comision,
                    'homeLocation' => [
                        '@type' => 'Place',
                        'name' => $ciudad,
                        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $ciudad, 'addressCountry' => 'PE'],
                    ],
                    // registrado en el RACC: lo une a la entidad de la portada
                    'affiliation' => ['@id' => Seo::idOrganizacion()],
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
        $ciudad = config('seo.ciudad');
        $descripcion = Str::limit(Html::texto($agrupacion->descripcion), 160)
            ?: "{$agrupacion->nombre}: agrupación de {$comision} en {$ciudad}. Integrantes, actividades y contacto.";

        return [[
            'titulo' => "{$agrupacion->nombre} · {$comision} en {$ciudad} | " . config('seo.marca'),
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
                    'genre' => $comision,
                    'location' => [
                        '@type' => 'Place',
                        'name' => $ciudad,
                        'address' => ['@type' => 'PostalAddress', 'addressLocality' => $ciudad, 'addressCountry' => 'PE'],
                    ],
                    'memberOf' => ['@id' => Seo::idOrganizacion()],
                    // solo nombres: el DNI de los integrantes nunca sale al público
                    'member' => $agrupacion->integrantes
                        ->map(fn ($i) => ['@type' => 'Person', 'name' => $i->nombre_completo])
                        ->all(),
                ]),
                $this->migas([['Agrupaciones', "{$base}/agrupaciones"], [$agrupacion->nombre, $url]]),
            ],
        ], 200];
    }

    // /agenda/{id}-{slug}: se busca por id; el slug es decorativo
    private function metaEvento(string $segmento): array
    {
        $id = preg_match('/^(\d+)(?:-|$)/', $segmento, $m) ? (int) $m[1] : null;
        $evento = $id ? Evento::publicado()->with(['comision:codigo,nombre', 'persona', 'agrupacion'])->find($id) : null;

        if (! $evento) {
            return [$this->noEncontrado(), 404];
        }

        $base = $this->base();
        // canónica con el slug actual: si cambió el título, Google indexa la URL nueva
        $url = "{$base}/agenda/{$evento->ruta_publica}";
        $cuando = $evento->fecha_inicio->locale('es')->isoFormat('dddd D [de] MMMM, HH:mm');
        $donde = $evento->modalidad === 'virtual' ? 'virtual' : $evento->lugar;
        $descripcion = Str::limit(Html::texto($evento->descripcion), 160)
            ?: "{$evento->titulo}: {$cuando} · {$donde}.";

        // quién lo organiza: la municipalidad (la entidad del sitio) o el artista / la agrupación
        $org = $evento->organizador();
        $organizador = match ($org['tipo']) {
            'persona' => array_filter(['@type' => 'Person', 'name' => $org['nombre'],
                'url' => $org['slug'] ? "{$base}/consejeros/{$org['slug']}" : null]),
            'agrupacion' => array_filter(['@type' => 'PerformingGroup', 'name' => $org['nombre'],
                'url' => $org['slug'] ? "{$base}/agrupaciones/{$org['slug']}" : null]),
            default => ['@id' => Seo::idOrganizacion()],
        };

        return [[
            'titulo' => "{$evento->titulo} · {$evento->fecha_inicio->locale('es')->isoFormat('D [de] MMMM')} en " . config('seo.ciudad') . ' | ' . config('seo.marca'),
            'descripcion' => $descripcion,
            'canonica' => $url,
            'imagen' => $evento->imagen_url,
            'jsonld' => [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Event',
                    'name' => $evento->titulo,
                    'url' => $url,
                    'image' => $evento->imagen_url,
                    'description' => $descripcion,
                    // hora de pared de la ciudad, con su zona explícita
                    'startDate' => $evento->fecha_inicio->shiftTimezone(Evento::ZONA)->toIso8601String(),
                    'endDate' => $evento->fecha_fin?->shiftTimezone(Evento::ZONA)->toIso8601String(),
                    'eventStatus' => 'https://schema.org/EventScheduled',
                    'eventAttendanceMode' => $evento->modalidad === 'virtual'
                        ? 'https://schema.org/OnlineEventAttendanceMode'
                        : 'https://schema.org/OfflineEventAttendanceMode',
                    'location' => $evento->modalidad === 'virtual'
                        ? ['@type' => 'VirtualLocation', 'url' => $evento->enlace]
                        : array_filter([
                            '@type' => 'Place',
                            'name' => $evento->lugar,
                            'address' => trim(($evento->direccion ? "{$evento->direccion}, " : '') . config('seo.ciudad')),
                        ]),
                    'isAccessibleForFree' => $evento->flag_gratuito,
                    // Google pide "offers" en eventos: gratis = precio 0
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => $evento->flag_gratuito ? 0 : (float) $evento->costo,
                        'priceCurrency' => 'PEN',
                        'url' => $url,
                        'availability' => 'https://schema.org/InStock',
                    ],
                    'organizer' => $organizador,
                    'inLanguage' => 'es-PE',
                ]),
                $this->migas([['Agenda cultural', "{$base}/agenda"], [$evento->titulo, $url]]),
            ],
        ], 200];
    }

    private function noEncontrado(): array
    {
        return ['titulo' => 'Página no encontrada | ' . config('seo.marca'), 'robots' => 'noindex'];
    }

    // páginas de listado (directorio, agenda): colección publicada por la organización
    private function coleccion(string $nombre, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $nombre,
            'url' => $url,
            'inLanguage' => 'es-PE',
            'isPartOf' => ['@id' => Seo::idSitio()],
            'publisher' => ['@id' => Seo::idOrganizacion()],
            'about' => ['@type' => 'Place', 'name' => config('seo.ciudad') . ', Perú'],
        ];
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
        return Seo::base();
    }

    // ---------- armado del <head> ----------

    private function inyectar(string $html, array $meta): string
    {
        $e = fn (?string $texto) => htmlspecialchars((string) $texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $descripcion = $meta['descripcion'] ?? config('seo.descripcion');

        // sin imagen propia (portada, listados, perfiles sin foto) va la imagen de la
        // marca: un link sin imagen en WhatsApp/Facebook se ve vacío y se comparte menos
        $imagenPropia = $meta['imagen'] ?? null;
        $meta['imagen'] = $imagenPropia ?: Seo::url(config('seo.imagen'));
        $imagenAlt = $imagenPropia ? $meta['titulo'] : config('seo.imagen_alt');

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
            '<meta property="og:image" content="' . $e($meta['imagen']) . '">',
            '<meta property="og:image:alt" content="' . $e($imagenAlt) . '">',
            // medidas solo de la imagen de marca (las subidas por usuarios varían)
            $imagenPropia ? null : '<meta property="og:image:width" content="' . config('seo.imagen_ancho') . '">',
            $imagenPropia ? null : '<meta property="og:image:height" content="' . config('seo.imagen_alto') . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . $e($meta['titulo']) . '">',
            '<meta name="twitter:description" content="' . $e($descripcion) . '">',
            '<meta name="twitter:image" content="' . $e($meta['imagen']) . '">',
            // señales geográficas: el registro es de Puno, Perú
            '<meta name="geo.region" content="PE-PUN">',
            '<meta name="geo.placename" content="' . $e(config('seo.ciudad')) . '">',
            '<link rel="alternate" type="text/plain" title="llms.txt" href="' . $e(Seo::url('llms.txt')) . '">',
        ]);

        // JSON_HEX_TAG: un "</script>" escrito por un usuario no puede cerrar el bloque
        foreach ($meta['jsonld'] ?? [] as $dato) {
            $etiquetas[] = '<script type="application/ld+json">'
                . json_encode($dato, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)
                . '</script>';
        }

        // fuera la descripción genérica del build; el título se reemplaza
        // el build minificado escribe name=description SIN comillas: con el patrón
        // anterior (que las exigía) cada página salía con dos meta description
        $html = preg_replace('/<meta\s+name=["\']?description["\']?[^>]*>\s*/i', '', $html);
        $html = preg_replace('/<title>.*?<\/title>/is', '<title>' . $e($meta['titulo']) . '</title>', $html, 1);
        // idioma del documento para buscadores y lectores de pantalla (por si el build no lo trae)
        $html = preg_replace('/<html(?![^>]*\blang=)([^>]*)>/i', '<html lang="es-PE"$1>', $html, 1);

        return str_replace('</head>', '    ' . implode("\n    ", $etiquetas) . "\n  </head>", $html);
    }
}
