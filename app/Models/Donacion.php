<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Donacion extends Model
{
    protected $table = 'donaciones';

    protected $primaryKey = 'id_donacion';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_donador',
        'id_campana',
        'id_usuario_registrador',
        'fecha_recepcion',
        'estado',
        'evidencia_url',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'created_at' => 'datetime',
        ];
    }

    public function especie(): HasOne
    {
        return $this->hasOne(DonacionEspecie::class, 'id_donacion', 'id_donacion');
    }

    public function economica(): HasOne
    {
        return $this->hasOne(DonacionEconomica::class, 'id_donacion', 'id_donacion');
    }

    public function donador(): BelongsTo
    {
        return $this->belongsTo(Donador::class, 'id_donador', 'id_donador');
    }

    public function campana(): BelongsTo
    {
        return $this->belongsTo(Campana::class, 'id_campana', 'id_campana');
    }

    public function usuarioRegistrador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_registrador', 'id_usuario');
    }

    public function entregas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Entrega::class, 'id_donacion', 'id_donacion');
    }
}
