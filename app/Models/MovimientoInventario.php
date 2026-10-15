<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $primaryKey = 'id_movimiento';

    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Los movimientos de inventario son inmutables.');
        });

        static::deleting(function (): never {
            throw new LogicException('Los movimientos de inventario son inmutables.');
        });
    }

    protected $fillable = [
        'id_inventario',
        'id_usuario',
        'tipo_movimiento',
        'cantidad',
        'cantidad_anterior',
        'cantidad_posterior',
        'tipo_referencia',
        'id_referencia',
        'motivo',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'cantidad_anterior' => 'decimal:2',
            'cantidad_posterior' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }
}
