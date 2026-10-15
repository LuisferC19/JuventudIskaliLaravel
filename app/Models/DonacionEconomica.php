<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonacionEconomica extends Model
{
    protected $table = 'donaciones_economicas';

    protected $primaryKey = 'id_donacion';

    public $timestamps = false;
    protected $fillable = [
        'id_donacion',
        'monto',
        'moneda',
        'metodo_pago',
        'referencia_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function donacion(): BelongsTo
    {
        return $this->belongsTo(Donacion::class, 'id_donacion', 'id_donacion');
    }
}
