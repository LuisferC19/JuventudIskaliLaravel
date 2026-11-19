<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvanceCampana extends Model
{
    protected $table = 'avances_campana';

    protected $primaryKey = 'id_avance';

    public $timestamps = false;
    protected $fillable = [
        'id_campana',
        'id_usuario',
        'descripcion',
        'porcentaje_avance',
        'cantidad_acopiada',
        'evidencia_url',
        'fecha_registro',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_avance' => 'decimal:2',
            'cantidad_acopiada' => 'decimal:2',
            'fecha_registro' => 'datetime',
        ];
    }
}
