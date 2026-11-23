<?php

return [
    'institucion' => [
        'nombre' => 'Colectivo Juventud Iskali',
        'lema' => 'Juventud que transforma',
        'firma_cargo' => 'Presidenta del Colectivo',
        'firma_nombre' => 'Directiva Juventud Iskali',
    ],
    'umbral_minimo' => 50,
    'umbral_maximo' => 200,
    'umbral_defecto' => 100,
    'penalizacion_retardo' => (int) env('ISKALI_PEN_RETARDO', 2),
    'penalizacion_inasistencia' => (int) env('ISKALI_PEN_INASISTENCIA', 5),
];
