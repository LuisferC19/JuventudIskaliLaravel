<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoBien extends Model
{
    protected $table = 'tipos_bien';

    protected $primaryKey = 'id_tipo_bien';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'unidad_medida',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}
