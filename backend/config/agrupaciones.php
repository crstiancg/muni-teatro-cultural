<?php

return [

    // cuántas agrupaciones puede crear (representar) una misma persona.
    // Ser integrante de otras agrupaciones no cuenta para este límite.
    'max_por_representante' => (int) env('AGRUPACIONES_MAXIMO', 2),

];
