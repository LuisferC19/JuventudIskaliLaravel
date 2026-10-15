<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonacionEspecie extends Model
{
    protected $table = 'donaciones_especie';

    protected $primaryKey = 'id_donacion';

    public $timestamps = false;
    protected $fillable = [
        'id_donacion',
        'id_tipo_bien',
        'descripcion',
        'cantidad',
        'unidad_medida',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }

    public function donacion(): BelongsTo
    {
        return $this->belongsTo(Donacion::class, 'id_donacion', 'id_donacion');
    }

    public function tipoBien(): BelongsTo
    {
        return $this->belongsTo(TipoBien::class, 'id_tipo_bien', 'id_tipo_bien');
    }
}
