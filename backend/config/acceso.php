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

];
