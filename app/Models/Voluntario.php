<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionVoluntario::class, 'id_voluntario', 'id_voluntario');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(AsistenciaVoluntario::class, 'id_voluntario', 'id_voluntario');
    }
}
