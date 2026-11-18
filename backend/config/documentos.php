<?php

// Documentos oficiales del Consejo Provincial de Cultura (página /consejo).
// Viven en el portal de la municipalidad y en gob.pe: el backend los trae de esta
// LISTA BLANCA (DocumentoPublicoController), los guarda y los sirve desde el
// dominio del sitio. Nunca se descarga una URL que no esté acá.
return [

    // cada cuántos días se vuelve a pedir al portal (si falla, se sirve la copia)
    'cache_dias' => 7,

    'lista' => [
        'ra-277-2024' => [
            'titulo' => 'Resolución de Alcaldía N.° 277-2024-MPP/A',
            'fecha' => '2024-03-12',
            'descripcion' => 'Reconoce la Comisión Ejecutiva Permanente del Consejo Provincial de Cultura de Puno, gestión 2024.',
            'url' => 'https://cdn.www.gob.pe/uploads/document/file/6085059/5385120-res_alcaldia_277_2024.pdf',
        ],
        'ra-0571-2025' => [
            'titulo' => 'Resolución de Alcaldía N.° 0571-2025-MPP/A',
            'fecha' => '2025-05-05',
            'descripcion' => 'Reconoce la Comisión Ejecutiva Permanente del Consejo Provincial de Cultura de Puno, gestión 2025.',
            'url' => 'https://portal.munipuno.gob.pe/sites_old/default/files/Res-Alcaldia-2025/RES_Alcaldia_571_2025.pdf',
        ],
        'om-094-2026' => [
            'titulo' => 'Ordenanza Municipal N.° 094-2026-CMPP',
            'fecha' => null,
            'descripcion' => null,
            'url' => 'https://portal.munipuno.gob.pe/sites/default/files/ordenanza-municipal-2026/ORDENANZA%20MUNICIPAL%20N%C2%B0%20094-2026-CMPP.pdf',
        ],
    ],
];
