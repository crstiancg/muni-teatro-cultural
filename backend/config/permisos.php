<?php

// ÚNICA fuente de verdad de los permisos del panel.
//
// Para un módulo nuevo: agregarlo acá y listo. Se crea solo al correr
// "php artisan migrate" (en cada deploy) o con "php artisan permisos:sincronizar",
// y queda asignado al rol Administrador.
//
// Sacar algo de acá NO lo borra de la base (borrar permisos automáticamente
// podría dejar a alguien sin acceso): el comando solo avisa que sobra.

return [

    'rol_admin' => 'Administrador',

    // Rol de quien tiene ficha de persona: se asigna solo al crearse la ficha
    // (Persona::booted) y a los que ya existían en el primer deploy (permisos:sincronizar).
    'rol_artista' => 'Artista',

    // "Mi espacio" del panel. El Administrador NO los recibe (si no, vería "Mi
    // espacio" sin ser artista). Los exigen los controladores del artista.
    'artista' => [
        'artista-perfil' => 'Mi perfil público y Curriculum Vitae (formación, capacitaciones, actividades, ficha)',
        'artista-agrupaciones' => 'Mis agrupaciones',
        'artista-eventos' => 'Mis eventos (proponer a la agenda)',
    ],

    // Otros roles del panel. Se CREAN con estos permisos la primera vez (en el
    // migrate de cada deploy); después se administran desde el panel (Roles):
    // un deploy nunca les devuelve un permiso que se les quitó a mano.
    'roles' => [
        'Artista' => ['artista-perfil', 'artista-agrupaciones', 'artista-eventos'],

        // Admins de la Gerencia que no son el superadministrador. Gestionan el día
        // a día pero NO tocan usuarios, roles ni permisos (podrían darse el rol
        // Administrador a sí mismos) y NO borran nada (eso queda para el Administrador).
        'Administrador de la Gerencia' => [
            'admin-personas-index', 'admin-personas-crear', 'admin-personas-editar', 'admin-personas-aprobar',
            'admin-agrupaciones-index', 'admin-agrupaciones-aprobar',
            'admin-eventos-index', 'admin-eventos-crear', 'admin-eventos-editar', 'admin-eventos-aprobar',
            'admin-comisiones-index', 'admin-comisiones-crear', 'admin-comisiones-editar',
            'admin-profesiones-index', 'admin-profesiones-crear', 'admin-profesiones-editar',
            'admin-universidades-index', 'admin-universidades-crear', 'admin-universidades-editar',
            'admin-carreras-index', 'admin-carreras-crear', 'admin-carreras-editar',
        ],
    ],

    'guard' => 'api',

    // módulos con CRUD completo: genera admin-{modulo}-{accion}
    'modulos' => [
        'usuarios' => 'Usuarios',
        'roles' => 'Roles',
        'permisos' => 'Permisos',
        'personas' => 'Personas',
        'comisiones' => 'Comisiones',
        'profesiones' => 'Profesiones',
        'universidades' => 'Universidades',
        'carreras' => 'Carreras',
        'eventos' => 'Eventos de la agenda cultural',
    ],

    'acciones' => [
        'index' => 'Ver lista de',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar',
    ],

    // permisos fuera del CRUD
    'extra' => [
        // agrupaciones: las crean los artistas; el admin solo las ve y las revisa
        'admin-agrupaciones-index' => 'Ver lista de Agrupaciones',
        'admin-agrupaciones-aprobar' => 'Aprobar agrupaciones',
        // aprobar u observar el perfil público de un artista
        'admin-personas-aprobar' => 'Aprobar perfiles públicos',
        // aprobar u observar los eventos que proponen artistas y agrupaciones
        'admin-eventos-aprobar' => 'Aprobar eventos de la agenda',
    ],

];
