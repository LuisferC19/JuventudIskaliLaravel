<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermisoRol extends Model
{
    protected $table = 'permisos_rol';

    protected $primaryKey = 'id_permiso';

    public $timestamps = false;

    protected $fillable = [
        'id_rol',
        'modulo',
        'puede_ver',
        'puede_crear',
        'puede_editar',
        'puede_eliminar',
    ];

    protected function casts(): array
    {
        return [
            'puede_ver' => 'boolean',
            'puede_crear' => 'boolean',
            'puede_editar' => 'boolean',
            'puede_eliminar' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }
}
