<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenalizacionAsistencia extends Model
{
    protected $table = 'penalizaciones_asistencia';

    protected $primaryKey = 'id_penalizacion';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'anulada' => 'boolean',
        ];
    }
}
