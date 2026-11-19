<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function datos(): array
    {
        $inventario = $this->valor(fn () => DB::table('inventario')->count());

        $articulosPorMes = array_fill(0, 12, 0.0);
        foreach ($this->filas(fn () => DB::table('donaciones as d')
            ->leftJoin('donaciones_especie as de2', 'de2.id_donacion', '=', 'd.id_donacion')
            ->whereYear('d.fecha_recepcion', now()->year)
            ->select(['d.fecha_recepcion', 'de2.cantidad'])
            ->get()) as $row) {
            $month = (int) Carbon::parse($row->fecha_recepcion)->format('n') - 1;
            $articulosPorMes[$month] += (float) ($row->cantidad ?? 0);
        }
        $monthlyDemo = array_sum($articulosPorMes) === 0.0;
        if ($monthlyDemo) {
            $articulosPorMes = [0, 0, 10, 25, 15, 30, 0, 0, 0, 0, 0, 0];
        }

        $estadoDonaciones = $this->seriePorClave(
            fn () => DB::table('donaciones')->selectRaw('estado, COUNT(*) AS total')->groupBy('estado')->get(),
            ['verificada' => 0, 'recibida' => 0, 'pendiente' => 0, 'rechazada' => 0],
            ['verificada' => 9, 'recibida' => 4, 'pendiente' => 1, 'rechazada' => 1]
        );
        $productosDonados = $this->filas(fn () => DB::table('tipos_bien as tb')
            ->join('donaciones_especie as de', 'de.id_tipo_bien', '=', 'tb.id_tipo_bien')
            ->join('donaciones as d', 'd.id_donacion', '=', 'de.id_donacion')
            ->whereIn('d.estado', ['verificada', 'recibida'])
            ->groupBy('tb.id_tipo_bien', 'tb.nombre')
            ->orderByDesc('total')
            ->limit(5)
            ->select('tb.nombre')
            ->selectRaw('SUM(de.cantidad) AS total')
            ->get());
        $productosLabels = $productosDonados->pluck('nombre')->all();
        $productosData = $productosDonados->pluck('total')->map(fn ($total): float => (float) $total)->all();
        $productosDemo = $productosLabels === [];
        if ($productosDemo) {
            $productosLabels = ['Ropa', 'Arroz', 'Cobijas', 'Zapatos', 'Medicamentos'];
            $productosData = [24, 18, 15, 12, 8];
        }
        $estadoBeneficiarios = $this->seriePorClave(
            fn () => DB::table('beneficiarios')->selectRaw('estado, COUNT(*) AS total')->groupBy('estado')->get(),
            ['activo' => 0, 'en_espera' => 0, 'inactivo' => 0],
            ['activo' => 13, 'en_espera' => 3, 'inactivo' => 2],
            ['activo' => 'Activos', 'en_espera' => 'En espera', 'inactivo' => 'Inactivos']
        );
        $tiposDonador = $this->seriePorClave(
            fn () => DB::table('donadores')->selectRaw('tipo_donante, COUNT(*) AS total')->groupBy('tipo_donante')->get(),
            ['anonimo' => 0, 'persona' => 0, 'grupo' => 0, 'organizacion' => 0],
            ['anonimo' => 2, 'persona' => 8, 'grupo' => 3, 'organizacion' => 5],
            ['anonimo' => 'Anónimo', 'persona' => 'Persona', 'grupo' => 'Grupo', 'organizacion' => 'Organización']
        );
        $roles = $this->filas(fn () => DB::table('roles as r')
            ->leftJoin('usuarios as u', function ($join): void {
                $join->on('u.id_rol', '=', 'r.id_rol')->where('u.estatus', '=', 'ACTIVO');
            })
            ->where('r.activo', true)
            ->groupBy('r.id_rol', 'r.nombre')
            ->orderByDesc('total')
            ->selectRaw('r.nombre AS rol, COUNT(u.id_usuario) AS total')
            ->get());
        $rolesLabels = $roles->pluck('rol')->all();
        $rolesValues = $roles->pluck('total')->map(fn ($total): int => (int) $total)->all();
        $rolesDemo = empty($rolesValues) || array_sum($rolesValues) === 0;
        if ($rolesDemo) {
            $rolesLabels = ['SuperAdmin', 'Directiva', 'Tesorería', 'Coordinador', 'Voluntario', 'Donador'];
            $rolesValues = [2, 2, 2, 2, 2, 2];
        }

        $campanas = $this->filas(fn () => DB::table('campanas as c')
            ->leftJoin('donaciones as d', function ($join): void {
                $join->on('d.id_campana', '=', 'c.id_campana')
                    ->whereIn('d.estado', ['verificada', 'recibida']);
            })
            ->leftJoin('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->where('c.estado', 'activa')
            ->groupBy('c.id_campana', 'c.nombre', 'c.meta_cantidad', 'c.fecha_inicio')
            ->orderBy('c.fecha_inicio')
            ->limit(4)
            ->selectRaw('c.nombre, c.meta_cantidad, COALESCE(SUM(de.cantidad), 0) AS recaudado')
            ->get());
        $campanasDemo = $campanas->isEmpty();
        $campanasActivas = $campanasDemo
            ? [
                ['nombre' => 'Abrigo Invierno', 'pct' => 72, 'color' => 'var(--primary)'],
                ['nombre' => 'Juguetes Navidad', 'pct' => 45, 'color' => 'var(--amber)'],
                ['nombre' => 'Empleo Joven', 'pct' => 18, 'color' => '#3b6fd4'],
                ['nombre' => 'Medicina Todos', 'pct' => 8, 'color' => 'var(--danger)'],
            ]
            : $campanas->values()->map(function (object $row, int $index): array {
                $colors = ['var(--primary)', 'var(--amber)', '#3b6fd4', 'var(--danger)'];
                $meta = max(1, (float) $row->meta_cantidad);

                return [
                    'nombre' => $row->nombre,
                    'pct' => min(100, (int) round(((float) $row->recaudado / $meta) * 100)),
                    'color' => $colors[$index % count($colors)],
                ];
            })->all();

        $articulosTotal = $this->valor(fn () => DB::table('donaciones_especie as de')
            ->join('donaciones as d', 'd.id_donacion', '=', 'de.id_donacion')
            ->where('d.estado', 'verificada')
            ->sum('de.cantidad'));
        $articulosMes = $this->valor(fn () => DB::table('donaciones_especie as de')
            ->join('donaciones as d', 'd.id_donacion', '=', 'de.id_donacion')
            ->where('d.estado', 'verificada')
            ->whereMonth('d.fecha_recepcion', now()->month)
            ->whereYear('d.fecha_recepcion', now()->year)
            ->sum('de.cantidad'));

        return [
            'kpis' => [
                'articulos_total' => $articulosTotal,
                'articulos_mes' => $articulosMes,
                'beneficiarios' => $this->valor(fn () => DB::table('beneficiarios')->where('estado', 'activo')->count()),
                'campanas' => $this->valor(fn () => DB::table('campanas')->where('estado', 'activa')->count()),
                'entregas' => $this->valor(fn () => DB::table('entregas')->where('estado', 'completada')->count()),
                'donadores' => $this->valor(fn () => DB::table('donadores')->count()),
                'voluntarios' => $this->valor(fn () => DB::table('voluntarios')->where('activo', true)->count()),
                'inventario' => $inventario,
            ],
            'charts' => [
                'meses' => ['labels' => self::MESES, 'articulos' => $articulosPorMes, 'demo' => $monthlyDemo],
                'estados_donaciones' => ['labels' => ['Verificada', 'Recibida', 'Pendiente', 'Rechazada'], 'data' => array_values($estadoDonaciones['values']), 'demo' => $estadoDonaciones['demo']],
                'productos_mas_donados' => ['labels' => $productosLabels, 'data' => $productosData, 'demo' => $productosDemo],
                'beneficiarios' => ['labels' => array_values($estadoBeneficiarios['labels']), 'data' => array_values($estadoBeneficiarios['values']), 'demo' => $estadoBeneficiarios['demo']],
                'donadores' => ['labels' => array_values($tiposDonador['labels']), 'data' => array_values($tiposDonador['values']), 'demo' => $tiposDonador['demo']],
                'roles' => ['labels' => $rolesLabels, 'data' => $rolesValues, 'demo' => $rolesDemo],
            ],
            'campanas_activas' => $campanasActivas,
            'anio' => now()->year,
        ];
    }

    private function seriePorClave(callable $consulta, array $keys, array $demoValues, ?array $labels = null): array
    {
        $rows = $this->filas($consulta);
        foreach ($rows as $row) {
            $key = strtolower((string) ($row->estado ?? $row->tipo_donante ?? ''));
            if (array_key_exists($key, $keys)) {
                $keys[$key] = (int) $row->total;
            }
        }
        $demo = array_sum($keys) === 0;
        if ($demo) {
            $keys = $demoValues;
        }

        return [
            'values' => $keys,
            'labels' => $labels ?? array_combine(array_keys($keys), array_map(fn ($label): string => ucfirst(str_replace('_', ' ', $label)), array_keys($keys))),
            'demo' => $demo,
        ];
    }

    private function filas(callable $query): \Illuminate\Support\Collection
    {
        try {
            return collect($query());
        } catch (Throwable) {
            return collect();
        }
    }

    private function valor(callable $query): int|float
    {
        try {
            return $query() ?? 0;
        } catch (Throwable) {
            return 0;
        }
    }

}
