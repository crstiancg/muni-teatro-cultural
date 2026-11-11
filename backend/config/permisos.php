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
