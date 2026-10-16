<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Actividad extends Model
{
    protected $table = 'actividades';

    protected $primaryKey = 'id_actividad';

    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'zona',
        'estado',
        'id_responsable',
        'creado_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'creado_en' => 'datetime',
        ];
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_responsable', 'id_usuario');
    }
}
