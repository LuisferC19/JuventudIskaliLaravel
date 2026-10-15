<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Respaldo extends Model
{
    protected $table = 'respaldos';

    protected $primaryKey = 'id';

    public $timestamps = false;
    protected $fillable = [
        'tipo_operacion',
        'nombre_archivo',
        'formato',
        'nombre_bd',
        'tamanio_bytes',
        'usuario_id',
        'fechayhora',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fechayhora' => 'datetime',
        ];
    }
}
