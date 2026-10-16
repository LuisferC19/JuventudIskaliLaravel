<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'id_rol',
        'nombre',
        'apellido',
        'email',
        'contrasena_hash',
        'activo',
        'intentos_fallidos',
        'fecha_bloqueo_temporal',
        'ultimo_acceso',
    ];

    protected $hidden = ['contrasena_hash'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'intentos_fallidos' => 'integer',
            'fecha_bloqueo_temporal' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'contrasena_hash';
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function historialAccesos(): HasMany
    {
        return $this->hasMany(HistorialAcceso::class, 'id_usuario', 'id_usuario');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_usuario', 'id_usuario');
    }

    public function voluntario(): HasOne
    {
        return $this->hasOne(Voluntario::class, 'id_usuario', 'id_usuario');
    }
}
