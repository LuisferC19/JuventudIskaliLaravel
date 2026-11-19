<?php

namespace App\Services;

use App\Models\Usuario;

class PermisosUsuario
{
    private const MAPA = [
        'dashboard' => ['ver' => ['dashboard.view']],
        'usuarios' => [
            'ver' => ['users.view'],
            'crear' => ['users.create'],
            'editar' => ['users.create'],
            'eliminar' => ['roles.manage'],
        ],
        'voluntarios' => [
            'ver' => ['users.view', 'users.view_zone'],
            'crear' => ['attendance.scan'],
            'editar' => ['attendance.scan'],
            'eliminar' => ['users.block'],
        ],
        'planning' => [
            'ver' => ['events.create', 'events.edit'],
            'crear' => ['events.create'],
            'editar' => ['events.edit'],
            'eliminar' => ['events.edit'],
        ],
        'campanas' => [
            'ver' => ['campaigns.manage'],
            'crear' => ['campaigns.manage'],
            'editar' => ['campaigns.manage'],
            'eliminar' => ['campaigns.manage'],
        ],
        'donaciones' => [
            'ver' => ['donations.create', 'reports.generate'],
            'crear' => ['donations.create'],
            'editar' => ['donations.create'],
            'eliminar' => ['donations.create'],
        ],
        'donadores' => [
            'ver' => ['donors.manage'],
            'crear' => ['donors.manage'],
            'editar' => ['donors.manage'],
            'eliminar' => ['donors.manage'],
        ],
        'beneficiarios' => [
            'ver' => ['beneficiaries.manage'],
            'crear' => ['beneficiaries.manage'],
            'editar' => ['beneficiaries.manage'],
            'eliminar' => ['beneficiaries.manage'],
        ],
        'entregas' => [
            'ver' => ['deliveries.view'],
            'crear' => ['deliveries.create'],
            'editar' => ['deliveries.create'],
            'eliminar' => ['deliveries.create'],
        ],
        'inventario' => [
            'ver' => ['inventory.view'],
            'editar' => ['inventory.adjust'],
        ],
        'gamificacion' => [
            'ver' => ['donors.manage', 'rewards.manage', 'penalties.manage'],
            'crear' => ['rewards.manage'],
            'editar' => ['rewards.manage'],
            'eliminar' => ['rewards.manage'],
        ],
        'reportes' => ['ver' => ['reports.generate']],
        'respaldos' => [
            'ver' => ['backups.manage'],
            'crear' => ['backups.manage'],
            'editar' => ['backups.manage'],
        ],
        'notificaciones' => [
            'ver' => '*',
            'editar' => '*',
        ],
    ];

    public function usuarioPuede(?Usuario $usuario, string $modulo, ?string $accion = null): bool
    {
        if (! $usuario || $usuario->estatus !== Usuario::ESTATUS_ACTIVO) {
            return false;
        }

        $modulo = mb_strtolower($modulo);

        if ($accion === null) {
            return $usuario->tienePermiso($modulo);
        }

        $accion = mb_strtolower($accion);
        $permisos = self::MAPA[$modulo][$accion] ?? null;
        if ($permisos === null) {
            return false;
        }

        if ($permisos === '*') {
            return true;
        }

        foreach ($permisos as $permiso) {
            if ($usuario->tienePermiso($permiso)) {
                return true;
            }
        }

        return false;
    }
}
