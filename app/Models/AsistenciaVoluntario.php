<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaVoluntario extends Model
{
    protected $table = 'asistencia_voluntarios';

    protected $primaryKey = 'id_asistencia';

    public $timestamps = false;
    protected $fillable = [
        'id_voluntario',
        'id_actividad',
        'fecha_hora',
        'metodo',
        'presente',
        'token_qr',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }
}
