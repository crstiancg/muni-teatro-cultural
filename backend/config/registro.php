<?php

// Registro de Agentes Culturales: lo que se imprime en la ficha (PDF) y en la
// página pública de verificación del QR.
return [

    // CPC + número con 4 dígitos: CPC0001. Pasado el 9999 sigue creciendo (CPC10000).
    'prefijo' => env('REGISTRO_PREFIJO', 'CPC'),

    'digitos' => 4,

    'entidad' => env('REGISTRO_ENTIDAD', 'Municipalidad Provincial de Puno'),

    'gerencia' => env('REGISTRO_GERENCIA', 'Gerencia de Cultura y Turismo'),

    'consejo' => env('REGISTRO_CONSEJO', 'Consejo Provincial de Cultura'),

    'nombre_registro' => env(
        'REGISTRO_NOMBRE',
        'Registro de Agentes Culturales y Portafolio del Consejo Provincial de Cultura de Puno'
    ),

    // Nombre corto por grupo de comisión (cod_grupo). En la base vienen como
    // "Comision especializada de musica": sin tildes y largos para un documento.
    // Mismos nombres que el portal (fronted/src/config/institucion.js → COMISIONES).
    'comisiones' => [
        '01' => 'Música',
        '02' => 'Danza',
        '03' => 'Artes escénicas',
        '04' => 'Literatura y oralidad',
        '05' => 'Bordado y trajes típicos',
        '06' => 'Audiovisuales',
        '07' => 'Artes plásticas',
        '08' => 'Gestión cultural',
    ],

    // color de la ficha PDF: negro | azul (#004173)
    'tema' => env('REGISTRO_TEMA', 'negro'),

    // las fechas se guardan en UTC; en la ficha van en hora de Perú
    'zona_horaria' => env('REGISTRO_ZONA_HORARIA', 'America/Lima'),

    // Dominio público que se imprime en el QR. Vacío = FRONTEND_URL.
    'verificacion_url' => env('REGISTRO_VERIFICACION_URL'),
];
