<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campamento extends Model
{
    protected $table = 'campamentos';

    protected $primaryKey = 'id_campamento';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'zona',
        'cupo',
        'umbral_puntos',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
