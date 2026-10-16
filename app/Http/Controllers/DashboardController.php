<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $anio = now()->year;
        $donacionesPorMes = DB::table('donaciones as d')
            ->leftJoin('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('donaciones_especie as des', 'des.id_donacion', '=', 'd.id_donacion')
            ->whereYear('d.fecha_recepcion', $anio)
            ->selectRaw('MONTH(d.fecha_recepcion) as mes')
            ->selectRaw('SUM(COALESCE(de.monto, 0)) as economica')
            ->selectRaw('SUM(COALESCE(des.cantidad, 0)) as especie')
            ->groupByRaw('MONTH(d.fecha_recepcion)')
            ->get()
            ->keyBy('mes');

        $montosEconomicos = [];
        $cantidadesEspecie = [];
        foreach (range(1, 12) as $mes) {
            $montosEconomicos[] = (float) ($donacionesPorMes->get($mes)->economica ?? 0);
            $cantidadesEspecie[] = (float) ($donacionesPorMes->get($mes)->especie ?? 0);
        }

        $demoMensual = array_sum($montosEconomicos) === 0.0 && array_sum($cantidadesEspecie) === 0.0;
        if ($demoMensual) {
            $montosEconomicos = [0, 0, 5200, 18000, 25000, 42000, 0, 0, 0, 0, 0, 0];
            $cantidadesEspecie = [0, 0, 10, 25, 15, 30, 0, 0, 0, 0, 0, 0];
        }

        $estadosDonacion = $this->conteosPorCampo('donaciones', 'estado', [
            'verificada' => 0,
            'recibida' => 0,
            'pendiente' => 0,
            'rechazada' => 0,
        ]);
        $demoEstados = array_sum($estadosDonacion) === 0;
        if ($demoEstados) {
            $estadosDonacion = ['verificada' => 9, 'recibida' => 4, 'pendiente' => 1, 'rechazada' => 1];
        }

        $tiposDonacion = [
            'economica' => DB::table('donaciones_economicas')->count(),
            'especie' => DB::table('donaciones_especie')->count(),
        ];
        $demoTiposDonacion = array_sum($tiposDonacion) === 0;
        if ($demoTiposDonacion) {
            $tiposDonacion = ['economica' => 6, 'especie' => 9];
        }

        $estadosBeneficiario = $this->conteosPorCampo('beneficiarios', 'estado', [
            'activo' => 0,
            'en_espera' => 0,
            'inactivo' => 0,
        ]);
        $demoBeneficiarios = array_sum($estadosBeneficiario) === 0;
        if ($demoBeneficiarios) {
            $estadosBeneficiario = ['activo' => 13, 'en_espera' => 3, 'inactivo' => 2];
        }

        $tiposDonador = $this->conteosPorCampo('donadores', 'tipo_donante', [
            'anonimo' => 0,
            'persona' => 0,
            'grupo' => 0,
            'organizacion' => 0,
        ]);
        $demoDonadores = array_sum($tiposDonador) === 0;
        if ($demoDonadores) {
            $tiposDonador = ['anonimo' => 2, 'persona' => 8, 'grupo' => 3, 'organizacion' => 5];
        }

        $usuariosPorRol = DB::table('roles as r')
            ->leftJoin('usuarios as u', function ($join): void {
                $join->on('u.id_rol', '=', 'r.id_rol')->where('u.activo', true);
            })
            ->where('r.activo', true)
            ->select('r.nombre')
            ->selectRaw('COUNT(u.id_usuario) as total')
            ->groupBy('r.id_rol', 'r.nombre')
            ->orderByDesc('total')
            ->get();
        $rolesLabels = $usuariosPorRol->pluck('nombre')->all();
        $rolesData = $usuariosPorRol->pluck('total')->map(fn ($total): int => (int) $total)->all();
        $demoRoles = $rolesLabels === [] || array_sum($rolesData) === 0;
        if ($demoRoles) {
            $rolesLabels = ['Administrador', 'Coordinador', 'Voluntario', 'Donador', 'Inventarista', 'Auditor', 'Comunicación'];
            $rolesData = [2, 1, 4, 6, 1, 1, 1];
        }

        $campanasActivas = DB::table('campanas as c')
            ->leftJoin('donaciones as d', function ($join): void {
                $join->on('d.id_campana', '=', 'c.id_campana')
                    ->whereIn('d.estado', ['verificada', 'recibida']);
            })
            ->leftJoin('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->where('c.estado', 'activa')
            ->select('c.nombre', 'c.meta_economica')
            ->selectRaw('COALESCE(SUM(de.monto), 0) as recaudado')
            ->groupBy('c.id_campana', 'c.nombre', 'c.meta_economica')
            ->orderBy('c.fecha_inicio')
            ->limit(4)
            ->get()
            ->map(function ($campana, int $index): array {
                $colores = ['var(--primary)', 'var(--amber)', '#3b6fd4', 'var(--danger)'];
                $meta = max(1, (float) $campana->meta_economica);

                return [
                    'nombre' => $campana->nombre,
                    'pct' => min(100, (int) round(((float) $campana->recaudado / $meta) * 100)),
                    'color' => $colores[$index % count($colores)],
                ];
            })
            ->all();
        $demoCampanas = $campanasActivas === [];
        if ($demoCampanas) {
            $campanasActivas = [
                ['nombre' => 'Abrigo Invierno', 'pct' => 72, 'color' => 'var(--primary)'],
                ['nombre' => 'Juguetes Navidad', 'pct' => 45, 'color' => 'var(--amber)'],
                ['nombre' => 'Empleo Joven', 'pct' => 18, 'color' => '#3b6fd4'],
                ['nombre' => 'Medicina Todos', 'pct' => 8, 'color' => 'var(--danger)'],
            ];
        }

        $actividadReciente = $this->actividadReciente();
        $demoActividad = $actividadReciente === [];
        if ($demoActividad) {
            $actividadReciente = [
                ['texto' => 'Donación registrada', 'tiempo' => '5 min', 'color' => '#1A7A5E'],
                ['texto' => 'Entrega completada #142', 'tiempo' => '1 h', 'color' => '#3B6FD4'],
                ['texto' => 'Nuevo beneficiario', 'tiempo' => '2 h', 'color' => '#1A7A5E'],
                ['texto' => 'Stock bajo — Medicamentos', 'tiempo' => '3 h', 'color' => '#C68B0A'],
                ['texto' => 'Nuevo donador registrado', 'tiempo' => '5 h', 'color' => '#993556'],
            ];
        }

        return view('dashboard', [
            'totalDonado' => (float) DB::table('donaciones_economicas')->sum('monto'),
            'donadoEsteMes' => (float) DB::table('donaciones as d')
                ->join('donaciones_economicas as de', 'de.id_donacion', '=', 'd.id_donacion')
                ->whereMonth('d.fecha_recepcion', now()->month)
                ->whereYear('d.fecha_recepcion', $anio)
                ->sum('de.monto'),
            'beneficiariosActivos' => DB::table('beneficiarios')->where('estado', 'activo')->count(),
            'campanasActivasTotal' => DB::table('campanas')->where('estado', 'activa')->count(),
            'entregasCompletadas' => DB::table('entregas')->where('estado', 'completada')->count(),
            'totalDonadores' => DB::table('donadores')->count(),
            'voluntariosActivos' => DB::table('voluntarios')->where('activo', true)->count(),
            'tiposBienInventario' => DB::table('inventario')->count(),
            'meses' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'montosEconomicos' => $montosEconomicos,
            'cantidadesEspecie' => $cantidadesEspecie,
            'estadosDonacion' => array_values($estadosDonacion),
            'tiposDonacion' => array_values($tiposDonacion),
            'estadosBeneficiario' => array_values($estadosBeneficiario),
            'tiposDonador' => array_values($tiposDonador),
            'rolesLabels' => $rolesLabels,
            'rolesData' => $rolesData,
            'campanasActivas' => $campanasActivas,
            'actividadReciente' => $actividadReciente,
            'demoMensual' => $demoMensual,
            'demoEstados' => $demoEstados,
            'demoTiposDonacion' => $demoTiposDonacion,
            'demoBeneficiarios' => $demoBeneficiarios,
            'demoDonadores' => $demoDonadores,
            'demoRoles' => $demoRoles,
            'demoCampanas' => $demoCampanas,
            'demoActividad' => $demoActividad,
        ]);
    }

    private function conteosPorCampo(string $tabla, string $campo, array $valores): array
    {
        foreach (DB::table($tabla)->select($campo)->selectRaw('COUNT(*) as total')->groupBy($campo)->get() as $fila) {
            if (array_key_exists($fila->{$campo}, $valores)) {
                $valores[$fila->{$campo}] = (int) $fila->total;
            }
        }

        return $valores;
    }

    private function actividadReciente(): array
    {
        $eventos = collect();
        $fuentes = [
            ['donaciones', 'created_at', 'Donación registrada', '#1A7A5E'],
            ['beneficiarios', 'created_at', 'Nuevo beneficiario', '#1A7A5E'],
            ['donadores', 'created_at', 'Nuevo donador registrado', '#993556'],
        ];

        foreach ($fuentes as [$tabla, $fecha, $etiqueta, $color]) {
            $eventos = $eventos->concat(DB::table($tabla)
                ->whereNotNull($fecha)
                ->orderByDesc($fecha)
                ->limit(5)
                ->get([$fecha])
                ->map(fn ($fila): array => ['texto' => $etiqueta, 'fecha' => $fila->{$fecha}, 'color' => $color]));
        }

        $eventos = $eventos->concat(DB::table('entregas')
            ->where('estado', 'completada')
            ->orderByDesc('fecha_entrega')
            ->limit(5)
            ->get(['id_entrega', 'fecha_entrega'])
            ->map(fn ($fila): array => [
                'texto' => 'Entrega completada #' . $fila->id_entrega,
                'fecha' => $fila->fecha_entrega,
                'color' => '#3B6FD4',
            ]));

        return $eventos->sortByDesc('fecha')
            ->take(5)
            ->map(function (array $evento): array {
                $minutos = max(0, now()->diffInMinutes($evento['fecha']));
                $evento['tiempo'] = $minutos < 60
                    ? "{$minutos} min"
                    : ($minutos < 1440 ? floor($minutos / 60) . ' h' : floor($minutos / 1440) . ' d');
                unset($evento['fecha']);

                return $evento;
            })
            ->values()
            ->all();
    }
}
