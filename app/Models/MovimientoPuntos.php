<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoPuntos extends Model
{
    protected $table = 'movimientos_puntos';

    protected $primaryKey = 'id_movimiento_puntos';

    public $timestamps = false;

    protected $fillable = [
        'id_voluntario',
        'tipo',
        'puntos_canjeables',
        'puntos_campamento',
        'id_actividad',
        'id_canje',
        'motivo',
        'id_usuario',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'puntos_canjeables' => 'integer',
            'puntos_campamento' => 'integer',
            'fecha' => 'datetime',
        ];
    }
}
