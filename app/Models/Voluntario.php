<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voluntario extends Model
{
    protected $table = 'voluntarios';

    protected $primaryKey = 'id_voluntario';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario',
        'telefono_contacto',
        'zona_asignada',
        'disponibilidad',
        'activo',
        'fecha_ingreso',
        'notas',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_ingreso' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
