<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';

    protected $primaryKey = 'id_entrega';

    public $timestamps = false;
    protected $fillable = [
        'id_donacion',
        'id_beneficiario',
        'id_usuario_responsable',
        'cantidad_entregada',
        'fecha_entrega',
        'evidencia_url',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_entregada' => 'decimal:2',
            'fecha_entrega' => 'datetime',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
        ];
    }
}
