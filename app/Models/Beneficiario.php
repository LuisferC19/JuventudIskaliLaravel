<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Beneficiario extends Model
{
    protected $table = 'beneficiarios';

    protected $primaryKey = 'id_beneficiario';

    public $timestamps = true;
    protected $fillable = [
        'tipo_persona',
        'id_comunidad',
        'direccion',
        'telefono',
        'estado',
        'notas',
        'id_usuario_registrador',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function fisico(): HasOne
    {
        return $this->hasOne(BeneficiarioFisico::class, 'id_beneficiario', 'id_beneficiario');
    }

    public function moral(): HasOne
    {
        return $this->hasOne(BeneficiarioMoral::class, 'id_beneficiario', 'id_beneficiario');
    }

    public function comunidad(): BelongsTo
    {
        return $this->belongsTo(Comunidad::class, 'id_comunidad', 'id_comunidad');
    }

    public function tiposApoyo(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoApoyo::class,
            'beneficiario_tipos_apoyo',
            'id_beneficiario',
            'id_tipo_apoyo'
        )->using(BeneficiarioTipoApoyo::class)
            ->withPivot(['fecha_asignacion', 'activo', 'notas']);
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class, 'id_beneficiario', 'id_beneficiario');
    }
}
