<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CanjePremio extends Model
{
    protected $table = 'canjes_premios';

    protected $primaryKey = 'id_canje';

    public $timestamps = false;

    protected $fillable = [
        'id_voluntario',
        'id_premio',
        'puntos_usados',
        'estado',
        'fecha_solicitud',
        'fecha_entrega',
        'id_usuario_registra',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'puntos_usados' => 'integer',
            'fecha_solicitud' => 'datetime',
            'fecha_entrega' => 'datetime',
        ];
    }
}
