<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    protected $table = 'inventario';

    protected $primaryKey = 'id_inventario';

    public $timestamps = false;
    protected $fillable = [
        'id_tipo_bien',
        'cantidad_total_recibida',
        'cantidad_total_entregada',
        'ultima_actualizacion',
        'id_usuario_actualizador',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_total_recibida' => 'decimal:2',
            'cantidad_total_entregada' => 'decimal:2',
            'ultima_actualizacion' => 'datetime',
        ];
    }

    public function tipoBien(): BelongsTo
    {
        return $this->belongsTo(TipoBien::class, 'id_tipo_bien', 'id_tipo_bien');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_inventario', 'id_inventario');
    }
}
