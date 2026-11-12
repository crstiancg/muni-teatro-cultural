<?php

// SEO del portal público. El front es una SPA (el hosting no tiene Node), así
// que Laravel sirve su index.html con el <head> de cada página ya escrito:
// título, descripción, canónica, Open Graph y JSON-LD (App\Http\Controllers\SpaController).
//
// IDENTIDAD: todo lo que define "quién es" el sitio para Google vive acá y se
// usa igual en el <head>, el JSON-LD, el sitemap y llms.txt. Un solo nombre,
// una sola dirección, un solo logo: si cambian en un lugar y no en otro, Google
// no sabe si son la misma entidad (consistencia NAP: nombre, dirección, teléfono).
return [

    // nombre corto de la marca: va al final de cada <title> ("… · RACC Puno")
    'marca' => env('SEO_MARCA', 'RACC Puno'),

    // nombre descriptivo: lo que la gente busca en Google
    'nombre_largo' => env('SEO_NOMBRE', 'Registro de Agentes Culturales de Puno'),

    // se mantiene por compatibilidad (og:site_name, organizador por defecto)
    'sitio' => env('SEO_MARCA', 'RACC Puno'),

    'ciudad' => env('SEO_CIUDAD', 'Puno'),

    'descripcion' => env(
        'SEO_DESCRIPCION',
        'Directorio oficial de artistas, agrupaciones y agentes culturales de Puno, Capital Folclórica del Perú: música, danza, teatro, bordado, artes plásticas y agenda cultural.'
    ),

    // quién está detrás del registro (Organization / GovernmentOrganization)
    'organizacion' => [
        'nombre' => 'Consejo Provincial de Cultura de Puno',
        'madre' => 'Municipalidad Provincial de Puno',
        'madre_url' => env('SEO_MUNICIPALIDAD_URL', 'https://www.munipuno.gob.pe'),
        'direccion' => 'Jr. Deustua N.° 458',
        'localidad' => 'Puno',
        'region' => 'Puno',
        'codigo_postal' => '21001',
        'pais' => 'PE',
        'telefono' => '+51 51 601000',
        'correo' => 'alcaldia@munipuno.gob.pe',
        // perfiles oficiales (Facebook, Instagram, YouTube…), separados por coma.
        // Google los usa para confirmar que el sitio y esas cuentas son la misma entidad.
        'redes' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_REDES', ''))))),
    ],

    // rutas relativas al dominio (archivos en fronted/public, copiados por spa:publicar)
    'logo' => '/icons/logo-512.png',
    'imagen' => '/images/og-racc.jpg',
    'imagen_ancho' => 1200,
    'imagen_alto' => 630,
    'imagen_alt' => 'RACC Puno · Registro de Agentes Culturales de Puno, Capital Folclórica del Perú',

    // index.html del build de Quasar (lo copia `php artisan spa:publicar`)
    'spa_index' => env('SPA_INDEX', resource_path('spa/index.html')),

    // primer segmento de las rutas del panel: no se indexan (noindex + robots.txt)
    'rutas_privadas' => [
        'login', 'dashboard', 'perfil', 'curriculum-vitae', 'mis-agrupaciones', 'mis-eventos', 'personas',
        'comisiones', 'profesiones', 'universidades', 'carreras', 'usuarios', 'roles',
        'permisos', 'gestion',
    ],
];
