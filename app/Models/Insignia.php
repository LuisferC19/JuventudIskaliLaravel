<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Insignia extends Model
{
    protected $table = 'insignias';

    protected $primaryKey = 'id_insignia';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'descripcion',
        'puntos_otorgados',
        'imagen_url',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    public function donadores(): BelongsToMany
    {
        return $this->belongsToMany(Donador::class, 'donador_insignias', 'id_insignia', 'id_donador')
            ->using(DonadorInsignia::class)
            ->withPivot(['fecha_obtencion', 'id_usuario_asignador']);
    }
}
