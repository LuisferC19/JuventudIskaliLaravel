<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiarioMoral extends Model
{
    protected $table = 'beneficiarios_morales';

    protected $primaryKey = 'id_beneficiario';

    public $timestamps = false;
    protected $fillable = [
        'id_beneficiario',
        'razon_social',
        'rfc',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'id_beneficiario', 'id_beneficiario');
    }
}
