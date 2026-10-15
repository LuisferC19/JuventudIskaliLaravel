<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiarioFisico extends Model
{
    protected $table = 'beneficiarios_fisicos';

    protected $primaryKey = 'id_beneficiario';

    public $timestamps = false;
    protected $fillable = [
        'id_beneficiario',
        'nombre',
        'apellido',
        'edad',
        'curp',
        'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'id_beneficiario', 'id_beneficiario');
    }
}
