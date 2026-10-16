<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function voluntario(): BelongsTo
    {
        return $this->belongsTo(Voluntario::class, 'id_voluntario', 'id_voluntario');
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class, 'id_entrega', 'id_entrega');
    }

    public function asignador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_asignador', 'id_usuario');
    }
}
