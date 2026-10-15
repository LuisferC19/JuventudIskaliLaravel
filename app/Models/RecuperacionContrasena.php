<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecuperacionContrasena extends Model
{
    protected $table = 'recuperacion_contrasena';

    protected $primaryKey = 'id_recuperacion';

    public $timestamps = false;
    protected $fillable = [
        'id_usuario',
        'token',
        'fecha_solicitud',
        'fecha_expiracion',
        'usado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_solicitud' => 'datetime',
            'fecha_expiracion' => 'datetime',
            'usado' => 'boolean',
        ];
    }
}
