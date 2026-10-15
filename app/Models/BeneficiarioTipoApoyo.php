<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BeneficiarioTipoApoyo extends Pivot
{
    protected $table = 'beneficiario_tipos_apoyo';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id_beneficiario',
        'id_tipo_apoyo',
        'fecha_asignacion',
        'activo',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'datetime',
            'activo' => 'boolean',
        ];
    }
}
