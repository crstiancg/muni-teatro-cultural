<?php

return [

    // Usuarios ocultos (ej. el superadministrador del sistema): no aparecen en la
    // lista de usuarios y nadie más puede verlos, editarlos ni eliminarlos
    // (la API responde 404, como si no existieran). Ellos sí se ven entre sí.
    // En el .env: USUARIOS_OCULTOS=1  (varios separados por coma: 1,7)
    'usuarios_ocultos' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('USUARIOS_OCULTOS', '1'))
    ))),

    // Cómo ven los demás el NOMBRE de un usuario oculto donde aparece igual
    // (historial de revisiones, "Validado por" de la ficha PDF): la oficina, no
    // la persona. User::name lo aplica en un solo lugar para todo el sistema.
    'nombre_publico' => env('USUARIOS_OCULTOS_NOMBRE', 'Gerencia de Cultura y Turismo'),

];
