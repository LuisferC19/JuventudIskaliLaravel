<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionVoluntario extends Model
{
    protected $table = 'asignaciones_voluntario';

    protected $primaryKey = 'id_asignacion';

    public $timestamps = false;
    protected $fillable = [
        'id_voluntario',
        'id_entrega',
        'id_usuario_asignador',
        'fecha_asignacion',
        'fecha_compromiso',
        'estado',
        'observaciones_voluntario',
        'fecha_actualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'fecha_compromiso' => 'datetime',
            'fecha_actualizacion' => 'datetime',
        ];
    }
}
