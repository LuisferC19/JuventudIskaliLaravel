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
        $econTotal = $this->valor(fn () => DB::table('donaciones_economicas')->sum('monto'));
        $econMes = $this->valor(fn () => DB::table('donaciones as d')
            ->join('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->whereYear('d.fecha_recepcion', now()->year)
            ->whereMonth('d.fecha_recepcion', now()->month)
            ->sum('de.monto'));
        $inventario = $this->valor(fn () => DB::table('inventario')->count());

        $mensuales = array_fill(0, 12, 0.0);
        $especieMensual = array_fill(0, 12, 0.0);
        foreach ($this->filas(fn () => DB::table('donaciones as d')
            ->leftJoin('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('donaciones_especie as de2', 'de2.id_donacion', '=', 'd.id_donacion')
            ->whereYear('d.fecha_recepcion', now()->year)
            ->select(['d.fecha_recepcion', 'de.monto', 'de2.cantidad'])
            ->get()) as $row) {
            $month = (int) Carbon::parse($row->fecha_recepcion)->format('n') - 1;
            $mensuales[$month] += (float) ($row->monto ?? 0);
            $especieMensual[$month] += (float) ($row->cantidad ?? 0);
        }
        $monthlyDemo = array_sum($mensuales) === 0.0 && array_sum($especieMensual) === 0.0;
        if ($monthlyDemo) {
            $mensuales = [0, 0, 5200, 18000, 25000, 42000, 0, 0, 0, 0, 0, 0];
            $especieMensual = [0, 0, 10, 25, 15, 30, 0, 0, 0, 0, 0, 0];
        }

        $estadoDonaciones = $this->seriePorClave(
            fn () => DB::table('donaciones')->selectRaw('estado, COUNT(*) AS total')->groupBy('estado')->get(),
            ['verificada' => 0, 'recibida' => 0, 'pendiente' => 0, 'rechazada' => 0],
            ['verificada' => 9, 'recibida' => 4, 'pendiente' => 1, 'rechazada' => 1]
        );
        $tiposDonacion = [
            'Económica' => $this->valor(fn () => DB::table('donaciones_economicas')->count()),
            'En especie' => $this->valor(fn () => DB::table('donaciones_especie')->count()),
        ];
        $tipoDonacionDemo = array_sum($tiposDonacion) === 0;
        if ($tipoDonacionDemo) {
            $tiposDonacion = ['Económica' => 6, 'En especie' => 9];
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
                $join->on('u.id_rol', '=', 'r.id_rol')->where('u.activo', '=', true);
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
            $rolesLabels = ['Administrador', 'Coordinador', 'Voluntario', 'Donador', 'Inventarista', 'Auditor', 'Comunicación'];
            $rolesValues = [2, 1, 4, 6, 1, 1, 1];
        }

        $campanas = $this->filas(fn () => DB::table('campanas as c')
            ->leftJoin('donaciones as d', function ($join): void {
                $join->on('d.id_campana', '=', 'c.id_campana')
                    ->whereIn('d.estado', ['verificada', 'recibida']);
            })
            ->leftJoin('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->where('c.estado', 'activa')
            ->groupBy('c.id_campana', 'c.nombre', 'c.meta_economica', 'c.fecha_inicio')
            ->orderBy('c.fecha_inicio')
            ->limit(4)
            ->selectRaw('c.nombre, c.meta_economica, COALESCE(SUM(de.monto), 0) AS recaudado')
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
                $meta = max(1, (float) $row->meta_economica);

                return [
                    'nombre' => $row->nombre,
                    'pct' => min(100, (int) round(((float) $row->recaudado / $meta) * 100)),
                    'color' => $colors[$index % count($colors)],
                ];
            })->all();

        return [
            'kpis' => [
                'donaciones_total' => $this->moneda((float) $econTotal),
                'donaciones_mes' => $this->moneda((float) $econMes),
                'beneficiarios' => $this->valor(fn () => DB::table('beneficiarios')->where('estado', 'activo')->count()),
                'campanas' => $this->valor(fn () => DB::table('campanas')->where('estado', 'activa')->count()),
                'entregas' => $this->valor(fn () => DB::table('entregas')->where('estado', 'completada')->count()),
                'donadores' => $this->valor(fn () => DB::table('donadores')->count()),
                'voluntarios' => $this->valor(fn () => DB::table('voluntarios')->where('activo', true)->count()),
                'inventario' => $inventario,
            ],
            'charts' => [
                'meses' => ['labels' => self::MESES, 'montos' => $mensuales, 'especie' => $especieMensual, 'demo' => $monthlyDemo],
                'estados_donaciones' => ['labels' => ['Verificada', 'Recibida', 'Pendiente', 'Rechazada'], 'data' => array_values($estadoDonaciones['values']), 'demo' => $estadoDonaciones['demo']],
                'tipos_donacion' => ['labels' => array_keys($tiposDonacion), 'data' => array_values($tiposDonacion), 'demo' => $tipoDonacionDemo],
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

    private function moneda(float $cantidad): string
    {
        return '$'.number_format($cantidad, 2, '.', ',');
    }
}
