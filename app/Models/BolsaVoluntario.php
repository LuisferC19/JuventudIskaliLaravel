<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BolsaVoluntario extends Model
{
    protected $table = 'bolsa_voluntario';

    protected $primaryKey = 'id_bolsa';

    public $timestamps = false;

    protected $fillable = [
        'id_voluntario',
        'puntos_canjeables',
        'puntos_campamento',
        'faltas_injustificadas',
        'campamento_bloqueado',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'puntos_canjeables' => 'integer',
            'puntos_campamento' => 'integer',
            'faltas_injustificadas' => 'integer',
            'campamento_bloqueado' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }
}
