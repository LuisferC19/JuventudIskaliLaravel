<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActividadRequest;
use App\Models\Actividad;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActividadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:planning,ver')->only(['index', 'actividadesMes']);
        $this->middleware('permiso:planning,crear')->only(['create', 'store']);
        $this->middleware('permiso:planning,editar')->only(['edit', 'update', 'actualizarEstado']);
        $this->middleware('permiso:planning,eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $datosMes = $request->validate([
            'mes' => ['nullable', 'integer', 'between:1,12'],
            'anio' => ['nullable', 'integer', 'between:2000,2100'],
            'zona' => ['nullable', 'in:todas,san_martin,tlaxcala,ambas'],
        ]);
        $mes = (int) ($datosMes['mes'] ?? now()->month);
        $anio = (int) ($datosMes['anio'] ?? now()->year);
        $zona = $datosMes['zona'] ?? 'todas';
        $fechaMes = CarbonImmutable::create($anio, $mes, 1);
        $actividadesMes = $this->consultaMes($mes, $anio)->get();
        $actividadesPorDia = $actividadesMes->groupBy(
            fn (Actividad $actividad): int => (int) $actividad->fecha_inicio->day
        );
        $actividades = $this->actividadesFiltradas($zona)->orderByDesc('fecha_inicio')->get();
        $inicioCalendario = $fechaMes->startOfWeek(CarbonImmutable::MONDAY);
        $diasCalendario = [];

        for ($dia = $inicioCalendario; $dia->lte($fechaMes->endOfMonth()); $dia = $dia->addDay()) {
            $diasCalendario[] = $dia;
        }
        while (count($diasCalendario) % 7 !== 0) {
            $diasCalendario[] = null;
        }

        $kpis = [
            'total_actividades' => Actividad::count(),
            'actividades_semana' => Actividad::whereBetween('fecha_inicio', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'san_martin' => Actividad::whereIn('zona', ['san_martin', 'ambas'])->count(),
            'tlaxcala' => Actividad::whereIn('zona', ['tlaxcala', 'ambas'])->count(),
        ];
        $usuarios = DB::table('usuarios')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'email']);

        return view('planning', compact(
            'mes',
            'anio',
            'zona',
            'fechaMes',
            'diasCalendario',
            'actividadesPorDia',
            'actividades',
            'kpis',
            'usuarios'
        ));
    }

    public function create(): View
    {
        return view('actividades.form', [
            'actividad' => new Actividad([
                'fecha_inicio' => now()->startOfDay(),
                'fecha_fin' => now()->startOfDay(),
                'zona' => 'ambas',
                'estado' => 'planeada',
            ]),
            'usuarios' => $this->usuariosResponsables(),
        ]);
    }

    public function store(ActividadRequest $request): RedirectResponse
    {
        $actividad = Actividad::create($this->datosActividad(
            $request->validated(),
            (int) $request->user()->getAuthIdentifier()
        ));

        return redirect()->route('planning.index', [
            'mes' => $actividad->fecha_inicio->month,
            'anio' => $actividad->fecha_inicio->year,
        ])->with('success', 'Actividad creada correctamente.');
    }

    public function edit(string $actividad): View
    {
        return view('actividades.form', [
            'actividad' => Actividad::findOrFail($actividad),
            'usuarios' => $this->usuariosResponsables(),
        ]);
    }

    public function update(ActividadRequest $request, string $actividad): RedirectResponse
    {
        $registro = Actividad::findOrFail($actividad);
        $registro->update($this->datosActividad(
            $request->validated(),
            (int) $request->user()->getAuthIdentifier()
        ));

        return redirect()->route('planning.index', [
            'mes' => $registro->fecha_inicio->month,
            'anio' => $registro->fecha_inicio->year,
        ])->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(string $actividad): RedirectResponse
    {
        Actividad::findOrFail($actividad)->delete();

        return redirect()->route('planning.index')->with('success', 'Actividad eliminada correctamente.');
    }

    public function actualizarEstado(Request $request, string $actividad): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:planeada,en_curso,completada,cancelada'],
        ], [
            'estado.required' => 'Selecciona el estado de la actividad.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ]);

        Actividad::where('id_actividad', $actividad)->firstOrFail()->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
        ]);
    }

    public function actividadesMes(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $actividades = $this->consultaMes((int) $filtros['mes'], (int) $filtros['anio'])
            ->get()
            ->groupBy(fn (Actividad $actividad): int => (int) $actividad->fecha_inicio->day)
            ->map(fn ($registros) => $registros->map(fn (Actividad $actividad): array => [
                'id_actividad' => (int) $actividad->id_actividad,
                'titulo' => $actividad->titulo,
                'descripcion' => $actividad->descripcion,
                'fecha_inicio' => $actividad->fecha_inicio->toDateTimeString(),
                'fecha_fin' => $actividad->fecha_fin->toDateTimeString(),
                'zona' => $actividad->zona,
                'estado' => $actividad->estado,
                'id_responsable' => (int) $actividad->id_responsable,
                'dia' => (int) $actividad->fecha_inicio->day,
                'nombre' => $actividad->responsable?->nombre,
                'apellido' => $actividad->responsable?->apellido,
            ]));

        return response()->json([
            'success' => true,
            'actividades' => $actividades,
        ]);
    }

    private function consultaMes(int $mes, int $anio)
    {
        return $this->actividadesFiltradas('todas')
            ->with('responsable:id_usuario,nombre,apellido')
            ->whereMonth('fecha_inicio', $mes)
            ->whereYear('fecha_inicio', $anio)
            ->orderBy('fecha_inicio');
    }

    private function actividadesFiltradas(string $zona)
    {
        return Actividad::query()
            ->with('responsable:id_usuario,nombre,apellido')
            ->when($zona !== 'todas', function ($query) use ($zona): void {
                if ($zona === 'ambas') {
                    $query->whereIn('zona', ['san_martin', 'tlaxcala', 'ambas']);
                } else {
                    $query->where(function ($filtro) use ($zona): void {
                        $filtro->where('zona', $zona)->orWhere('zona', 'ambas');
                    });
                }
            });
    }

    private function datosActividad(array $datos, int $idUsuario): array
    {
        $datos['fecha_fin'] = $datos['fecha_fin'] ?: $datos['fecha_inicio'];
        $datos['id_responsable'] = $datos['id_usuario_responsable'] ?: $idUsuario;
        unset($datos['id_usuario_responsable']);

        return $datos;
    }

    private function usuariosResponsables()
    {
        return DB::table('usuarios')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'email']);
    }
}
