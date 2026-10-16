<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $primaryKey = 'id_notificacion';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'destinatario_email',
        'tipo',
        'canal',
        'asunto',
        'mensaje',
        'enviado_email',
        'leida',
        'id_referencia',
        'id_tipo_referencia',
        'fecha_envio',
    ];

    protected function casts(): array
    {
        return [
            'enviado_email' => 'boolean',
            'leida' => 'boolean',
            'fecha_envio' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
