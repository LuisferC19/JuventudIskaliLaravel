<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NivelGamificacion extends Model
{
    protected $table = 'niveles_gamificacion';

    protected $primaryKey = 'id_nivel';

    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'puntos_minimos',
        'puntos_maximos',
        'descripcion',
        'imagen_url',
    ];

    protected function casts(): array
    {
        return [

        ];
    }

    public function donadores(): HasMany
    {
        return $this->hasMany(Donador::class, 'id_nivel', 'id_nivel');
    }
}
