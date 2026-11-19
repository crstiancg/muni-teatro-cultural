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

    // nombre corto de la marca: va al final de cada <title> ("… · CPC Puno")
    'marca' => env('SEO_MARCA', 'CPC Puno'),

    // nombre descriptivo: lo que la gente busca en Google
    'nombre_largo' => env('SEO_NOMBRE', 'Registro de Agentes Culturales de Puno'),

    // se mantiene por compatibilidad (og:site_name, organizador por defecto)
    'sitio' => env('SEO_MARCA', 'CPC Puno'),

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
        // atención al público: la Gerencia de Cultura y Turismo. Igual que el footer
        // (fronted/src/config/institucion.js → atencion). El domicilio legal de la
        // municipalidad (Jr. Deustua 458) sigue en la política de privacidad.
        'direccion' => 'Pasaje Ramis N.° 359',
        'localidad' => 'Puno',
        'region' => 'Puno',
        'codigo_postal' => '21001',
        'pais' => 'PE',
        'telefono' => '+51 987 296 258',
        'correo' => 'alcaldia@munipuno.gob.pe',
        // perfiles oficiales (Facebook, Instagram, YouTube…), separados por coma.
        // Google los usa para confirmar que el sitio y esas cuentas son la misma entidad.
        // ?: y no el default de env(): en Dokploy la variable existe pero vacía
        'redes' => array_values(array_filter(array_map('trim', explode(',',
            (string) (env('SEO_REDES') ?: 'https://www.facebook.com/share/1G4VLHidc4/')
        )))),
    ],

    // rutas relativas al dominio (archivos en fronted/public, copiados por spa:publicar)
    'logo' => '/icons/logo-512.png',
    'imagen' => '/images/og-cpc.jpg',
    'imagen_ancho' => 1200,
    'imagen_alto' => 630,
    'imagen_alt' => 'CPC Puno · Registro de Agentes Culturales de Puno, Capital Folclórica del Perú',

    // index.html del build de Quasar (lo copia `php artisan spa:publicar`)
    'spa_index' => env('SPA_INDEX', resource_path('spa/index.html')),

    // primer segmento de las rutas del panel: no se indexan (noindex + robots.txt)
    'rutas_privadas' => [
        'login', 'dashboard', 'perfil', 'curriculum-vitae', 'mis-agrupaciones', 'mis-eventos', 'personas',
        'comisiones', 'profesiones', 'universidades', 'carreras', 'usuarios', 'roles',
        'permisos', 'gestion',
        // verificación del QR de la ficha: pública, pero cada URL es de una persona
        'v',
    ],
];
