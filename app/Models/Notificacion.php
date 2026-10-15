<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
