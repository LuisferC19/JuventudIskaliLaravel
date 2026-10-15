<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Donador extends Model
{
    protected $table = 'donadores';

    protected $primaryKey = 'id_donador';

    public $timestamps = true;
    public const UPDATED_AT = null;

    protected $fillable = [
        'id_nivel',
        'tipo_donante',
        'puntos_acumulados',
        'email',
        'telefono',
        'activo',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function fisico(): HasOne
    {
        return $this->hasOne(DonadorFisico::class, 'id_donador', 'id_donador');
    }

    public function moral(): HasOne
    {
        return $this->hasOne(DonadorMoral::class, 'id_donador', 'id_donador');
    }

    public function grupo(): HasOne
    {
        return $this->hasOne(DonadorGrupo::class, 'id_donador', 'id_donador');
    }

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(NivelGamificacion::class, 'id_nivel', 'id_nivel');
    }

    public function donaciones(): HasMany
    {
        return $this->hasMany(Donacion::class, 'id_donador', 'id_donador');
    }

    public function insignias(): BelongsToMany
    {
        return $this->belongsToMany(Insignia::class, 'donador_insignias', 'id_donador', 'id_insignia')
            ->using(DonadorInsignia::class)
            ->withPivot(['fecha_obtencion', 'id_usuario_asignador']);
    }
}
