<?php

// SEO del portal público. El front es una SPA (el hosting no tiene Node), así
// que Laravel sirve su index.html con el <head> de cada página ya escrito:
// título, descripción, canónica, Open Graph y JSON-LD (App\Http\Controllers\SpaController).
return [

    'sitio' => env('APP_NAME', 'Registro Cultural'),

    'ciudad' => env('SEO_CIUDAD', 'Puno'),

    'descripcion' => env(
        'SEO_DESCRIPCION',
        'Registro municipal de artistas, agrupaciones y agentes culturales de Puno, capital folclórica del Perú.'
    ),

    // index.html del build de Quasar (lo copia `php artisan spa:publicar`)
    'spa_index' => env('SPA_INDEX', resource_path('spa/index.html')),

    // primer segmento de las rutas del panel: no se indexan (noindex + robots.txt)
    'rutas_privadas' => [
        'login', 'dashboard', 'perfil', 'curriculum-vitae', 'mis-agrupaciones', 'personas',
        'comisiones', 'profesiones', 'universidades', 'carreras', 'usuarios', 'roles',
        'permisos', 'gestion',
    ],
];
