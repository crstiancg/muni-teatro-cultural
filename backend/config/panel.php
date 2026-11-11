<?php

// Temas del panel que cada usuario puede elegir (se guardan en users.preferencias).
// Son opciones CURADAS: cada combinación tiene el contraste verificado en
// fronted/src/css/panel.scss. Para sumar una, agregarla acá Y definir sus
// colores allá (claro y oscuro).
//
// Esmeralda y ámbar NO son acentos a propósito: son los colores fijos de los
// estados "Publicado" y "Observado"; como acento se confundirían con ellos.

return [

    'temas' => [
        'acentos' => ['rojo', 'rosa', 'violeta', 'indigo', 'azul'],
        'neutros' => ['calido', 'frio'],
        'modos' => ['claro', 'oscuro'],
    ],

    // lo que ve quien nunca tocó el selector (rojo de la marca, neutros cálidos)
    'tema_por_defecto' => [
        'acento' => 'rojo',
        'neutro' => 'calido',
        'modo' => 'claro',
    ],

];
