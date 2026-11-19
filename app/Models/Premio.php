<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Premio extends Model
{
    protected $table = 'cat_premios';

    protected $primaryKey = 'id_premio';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'costo_puntos',
        'stock',
        'imagen_url',
        'activo',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'costo_puntos' => 'integer',
            'stock' => 'integer',
            'activo' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
