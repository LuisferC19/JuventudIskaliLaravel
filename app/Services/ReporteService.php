<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public function donadores(?string $desde, ?string $hasta): array
    {
        $query = DB::table('donadores as d')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->select([
                'd.id_donador',
                'd.tipo_donante',
                'd.email',
                'd.telefono',
                'd.activo',
                'd.created_at',
                'df.nombre',
                'df.apellido',
                'dm.razon_social',
                'dg.nombre_grupo',
            ]);

        $this->filtrarFechas($query, 'd.created_at', $desde, $hasta);

        $rows = $query->orderBy('d.created_at')->get()->map(function (object $row): array {
            $nombre = match ($row->tipo_donante) {
                'anonimo' => 'Anónimo',
                'grupo' => $row->nombre_grupo ?: 'Sin datos',
                'organizacion' => $row->razon_social ?: 'Sin datos',
                default => trim(($row->nombre ?? '').' '.($row->apellido ?? '')) ?: 'Sin datos',
            };

            return [
                'nombre' => $nombre,
                'email' => $row->email ?: '—',
                'telefono' => $row->telefono ?: '—',
                'tipo' => match ($row->tipo_donante) {
                    'persona' => 'Persona física',
                    'organizacion' => 'Organización',
                    'grupo' => 'Grupo',
                    'anonimo' => 'Anónimo',
                    default => $row->tipo_donante,
                },
                'estatus' => $row->activo ? 'Activo' : 'Inactivo',
                'fecha' => $row->created_at,
            ];
        })->all();

        return [
            'rows' => $rows,
            'summary' => [
                'Total' => count($rows),
                'Activos' => count(array_filter($rows, fn (array $row): bool => $row['estatus'] === 'Activo')),
                'Inactivos' => count(array_filter($rows, fn (array $row): bool => $row['estatus'] === 'Inactivo')),
            ],
        ];
    }

    public function beneficiarios(?string $desde, ?string $hasta): array
    {
        $query = DB::table('beneficiarios as b')
            ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('comunidades as c', 'c.id_comunidad', '=', 'b.id_comunidad')
            ->select([
                'b.tipo_persona',
                'b.estado',
                'b.created_at',
                'bf.nombre',
                'bf.apellido',
                'bf.edad',
                'bm.razon_social',
                'c.municipio',
            ]);

        $this->filtrarFechas($query, 'b.created_at', $desde, $hasta);

        $rows = $query->orderBy('bf.apellido')->orderBy('bf.nombre')->get()->map(fn (object $row): array => [
            'nombre' => $row->tipo_persona === 'moral' ? ($row->razon_social ?: '—') : ($row->nombre ?: '—'),
            'apellidos' => $row->tipo_persona === 'moral' ? '—' : ($row->apellido ?: '—'),
            'edad' => $row->edad,
            'municipio' => $row->municipio ?: '—',
            'estatus' => match ($row->estado) {
                'activo' => 'Activo',
                'en_espera' => 'En espera',
                'inactivo' => 'Inactivo',
                default => $row->estado,
            },
            'tipo' => $row->tipo_persona === 'moral' ? 'Moral' : 'Física',
            'fecha' => $row->created_at,
        ])->all();

        return [
            'rows' => $rows,
            'summary' => [
                'Total' => count($rows),
                'Activos' => count(array_filter($rows, fn (array $row): bool => $row['estatus'] === 'Activo')),
                'En espera' => count(array_filter($rows, fn (array $row): bool => $row['estatus'] === 'En espera')),
                'Inactivos' => count(array_filter($rows, fn (array $row): bool => $row['estatus'] === 'Inactivo')),
            ],
        ];
    }

    public function campanas(?string $desde, ?string $hasta): array
    {
        $query = DB::table('campanas')->select([
            'nombre',
            'descripcion',
            'fecha_inicio',
            'fecha_cierre',
            'estado',
            'meta_economica',
        ]);
        $this->filtrarFechas($query, 'fecha_inicio', $desde, $hasta);

        $rows = $query->orderByDesc('fecha_inicio')->get()->map(fn (object $row): array => [
            'nombre' => $row->nombre,
            'descripcion' => $row->descripcion ?: '—',
            'fecha_inicio' => $row->fecha_inicio,
            'fecha_cierre' => $row->fecha_cierre,
            'estatus' => match ($row->estado) {
                'activa' => 'Activa',
                'finalizada', 'cerrada' => 'Finalizada',
                'cancelada' => 'Cancelada',
                default => ucfirst(str_replace('_', ' ', $row->estado)),
            },
            'meta' => (float) $row->meta_economica,
        ])->all();

        return [
            'rows' => $rows,
            'summary' => [
                'Total campañas' => count($rows),
                'Meta económica acumulada' => '$'.number_format(array_sum(array_column($rows, 'meta')), 2),
            ],
        ];
    }

    private function filtrarFechas(Builder $query, string $column, ?string $desde, ?string $hasta): void
    {
        if ($desde !== null) {
            $query->whereDate($column, '>=', $desde);
        }

        if ($hasta !== null) {
            $query->whereDate($column, '<=', $hasta);
        }
    }
}
