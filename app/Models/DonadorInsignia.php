<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DonadorInsignia extends Pivot
{
    protected $table = 'donador_insignias';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_donador',
        'id_insignia',
        'fecha_obtencion',
        'id_usuario_asignador',
    ];

    protected function casts(): array
    {
        return ['fecha_obtencion' => 'datetime'];
    }
}
