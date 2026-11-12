<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Identidad SEO del sitio: una sola fuente para la URL base y para la entidad
// (organización + sitio web) que se referencia desde todas las páginas.
class Seo
{
    // valores de ejemplo que nunca deben llegar a Google como dominio
    private const BASES_INVALIDAS = ['tudominio', 'localhost', '127.0.0.1', 'example.com'];

    // URL pública sin barra final. Si FRONTEND_URL quedó con el valor de ejemplo
    // del .env (pasó en producción: canónica, sitemap y Open Graph apuntaban a
    // https://tudominio), se usa el dominio real del request y se deja aviso en
    // el log. En local (APP_ENV=local) se respeta lo configurado.
    public static function base(): string
    {
        $configurada = rtrim((string) config('app.frontend_url'), '/');

        if (app()->environment('local', 'testing') || ! Str::contains($configurada, self::BASES_INVALIDAS)) {
            return $configurada;
        }

        Log::warning("FRONTEND_URL tiene un valor de ejemplo ({$configurada}): se usa el dominio del request. Corrige el .env y corre config:cache.");

        return rtrim(request()->getSchemeAndHttpHost(), '/');
    }

    public static function url(string $ruta): string
    {
        return self::base() . '/' . ltrim($ruta, '/');
    }

    public static function idOrganizacion(): string
    {
        return self::base() . '/#organizacion';
    }

    public static function idSitio(): string
    {
        return self::base() . '/#sitio';
    }

    // la entidad que firma el registro. Las demás páginas la referencian por @id
    public static function organizacion(): array
    {
        $org = config('seo.organizacion');

        return array_filter([
            '@type' => 'GovernmentOrganization',
            '@id' => self::idOrganizacion(),
            'name' => config('seo.nombre_largo'),
            'alternateName' => array_values(array_unique([config('seo.marca'), 'RACC', $org['nombre']])),
            'url' => self::base() . '/',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => self::url(config('seo.logo')),
                'width' => 512,
                'height' => 512,
            ],
            'image' => self::url(config('seo.imagen')),
            'description' => config('seo.descripcion'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $org['direccion'],
                'addressLocality' => $org['localidad'],
                'addressRegion' => $org['region'],
                'postalCode' => $org['codigo_postal'],
                'addressCountry' => $org['pais'],
            ],
            'telephone' => $org['telefono'],
            'email' => $org['correo'],
            'areaServed' => [
                '@type' => 'AdministrativeArea',
                'name' => "Provincia de {$org['localidad']}, Perú",
            ],
            'parentOrganization' => [
                '@type' => 'GovernmentOrganization',
                'name' => $org['madre'],
                'url' => $org['madre_url'],
            ],
            'knowsAbout' => [
                'Festividad de la Virgen de la Candelaria',
                'Música andina', 'Danza folclórica', 'Sikuris', 'Artes escénicas',
                'Bordado y trajes típicos', 'Artes plásticas', 'Gestión cultural',
            ],
            'sameAs' => $org['redes'] ?: null,
        ]);
    }

    public static function sitio(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => self::idSitio(),
            'name' => config('seo.nombre_largo'),
            'alternateName' => config('seo.marca'),
            'url' => self::base() . '/',
            'inLanguage' => 'es-PE',
            'publisher' => ['@id' => self::idOrganizacion()],
            // cuadro de búsqueda del sitio: Google sabe que /consejeros?buscar= busca artistas
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => self::base() . '/consejeros?buscar={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
}
