<?php

namespace App\Services;

use App\Models\PermisoRol;
use App\Models\Usuario;
use Illuminate\Support\Collection;

class PermisosUsuario
{
    /** @var Collection<string, PermisoRol>|null */
    private ?Collection $permisos = null;

    private ?int $usuarioId = null;

    public function usuarioPuede(?Usuario $usuario, string $modulo, string $accion): bool
    {
        if (! $usuario) {
            return false;
        }

        if ($this->usuarioId !== $usuario->id_usuario) {
            $this->usuarioId = $usuario->id_usuario;
            $this->permisos = PermisoRol::where('id_rol', $usuario->id_rol)
                ->get()
                ->keyBy(fn (PermisoRol $permiso) => mb_strtolower($permiso->modulo));
        }

        $columna = [
            'ver' => 'puede_ver',
            'crear' => 'puede_crear',
            'editar' => 'puede_editar',
            'eliminar' => 'puede_eliminar',
        ][$accion] ?? null;

        return $columna !== null
            && (bool) ($this->permisos->get(mb_strtolower($modulo))?->{$columna} ?? false);
    }
}
