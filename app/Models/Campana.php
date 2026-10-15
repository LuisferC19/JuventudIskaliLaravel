<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campana extends Model
{
    protected $table = 'campanas';

    protected $primaryKey = 'id_campana';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_usuario_creador',
        'nombre',
        'descripcion',
        'tipo_meta',
        'meta_economica',
        'fecha_inicio',
        'fecha_cierre',
        'estado',
        'imagen_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_economica' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_cierre' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creador', 'id_usuario');
    }
}
