<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoApoyo extends Model
{
    protected $table = 'tipos_apoyo';

    protected $primaryKey = 'id_tipo_apoyo';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function beneficiarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Beneficiario::class,
            'beneficiario_tipos_apoyo',
            'id_tipo_apoyo',
            'id_beneficiario'
        )->using(BeneficiarioTipoApoyo::class)
            ->withPivot(['fecha_asignacion', 'activo', 'notas']);
    }
}
