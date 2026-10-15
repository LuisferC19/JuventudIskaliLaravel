<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAcceso extends Model
{
    protected $table = 'historial_accesos';

    protected $primaryKey = 'id_acceso';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_accion',
        'fecha_hora',
        'ip_address',
        'user_agent',
        'exitoso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'exitoso' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
