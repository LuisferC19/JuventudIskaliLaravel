<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class Usuario extends Authenticatable
{
    public const ESTATUS_PENDIENTE = 'PENDIENTE';

    public const ESTATUS_ACTIVO = 'ACTIVO';

    public const ESTATUS_INACTIVO = 'INACTIVO';

    public const ESTATUS_BLOQUEADO = 'BLOQUEADO';

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
        'estatus',
        'sede',
        'intentos_fallidos',
        'fecha_bloqueo_temporal',
        'ultimo_acceso',
    ];

    protected $hidden = ['contrasena_hash'];

    /** @var list<string>|null */
    private ?array $permisosSlugs = null;

    protected function casts(): array
    {
        return [
            'intentos_fallidos' => 'integer',
            'fecha_bloqueo_temporal' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function estaActivo(): bool
    {
        return $this->estatus === self::ESTATUS_ACTIVO;
    }

    public function estaPendiente(): bool
    {
        return $this->estatus === self::ESTATUS_PENDIENTE;
    }

    public function estaInactivo(): bool
    {
        return $this->estatus === self::ESTATUS_INACTIVO;
    }

    public function estaBloqueado(): bool
    {
        return $this->estatus === self::ESTATUS_BLOQUEADO;
    }

    public function tienePermiso(string $slug): bool
    {
        if ($this->permisosSlugs === null) {
            $this->permisosSlugs = DB::table('rol_permiso')
                ->join('permisos', 'permisos.id_permiso', '=', 'rol_permiso.id_permiso')
                ->where('rol_permiso.id_rol', $this->id_rol)
                ->pluck('permisos.slug')
                ->all();
        }

        return in_array($slug, $this->permisosSlugs, true);
    }

    public function esRol(string $slug): bool
    {
        return $this->rol?->slug === $slug;
    }

    public function zonasVisibles(): ?array
    {
        if ($this->tienePermiso('users.view')) {
            return null;
        }

        if (! $this->tienePermiso('users.view_zone')) {
            return [];
        }

        return match ($this->sede) {
            'ambas' => null,
            'san_martin' => ['san_martin', 'ambas'],
            'tlaxcala' => ['tlaxcala', 'ambas'],
            default => [],
        };
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estatus', self::ESTATUS_ACTIVO);
    }

    public function getAuthPassword(): string
    {
        $hash = (string) $this->contrasena_hash;

        return str_starts_with($hash, '$2b$')
            ? '$2y$'.substr($hash, 4)
            : $hash;
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
