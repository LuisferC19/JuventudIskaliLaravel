<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActividadRequest;
use App\Models\Actividad;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $filtros = $request->validate([
            'mes' => ['sometimes', 'integer', 'between:1,12'],
            'anio' => ['sometimes', 'integer', 'between:2000,2100'],
        ]);
        $fechaCalendario = CarbonImmutable::create(
            (int) ($filtros['anio'] ?? now()->year),
            (int) ($filtros['mes'] ?? now()->month),
            1
        );
        $inicio = $fechaCalendario->startOfMonth();
        $fin = $fechaCalendario->endOfMonth();
        $inicioSemana = now()->startOfWeek();
        $finSemana = now()->endOfWeek();

        return view('planning', [
            'fechaCalendario' => $fechaCalendario,
            'actividades' => DB::table('actividades as a')
                ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'a.id_responsable')
                ->whereBetween('a.fecha_inicio', [$inicio, $fin])
                ->orderBy('a.fecha_inicio')
                ->get(['a.*', 'u.nombre as responsable_nombre', 'u.apellido as responsable_apellido']),
            'totalActividades' => DB::table('actividades')->count(),
            'actividadesSemana' => DB::table('actividades')
                ->whereBetween('fecha_inicio', [$inicioSemana, $finSemana])
                ->count(),
            'actividadesSanMartin' => DB::table('actividades')->whereIn('zona', ['san_martin', 'ambas'])->count(),
            'actividadesTlaxcala' => DB::table('actividades')->whereIn('zona', ['tlaxcala', 'ambas'])->count(),
            'responsables' => DB::table('usuarios')
                ->where('estatus', 'ACTIVO')
                ->orderBy('nombre')
                ->get(['id_usuario', 'nombre', 'apellido']),
        ]);
    }

    public function create(): View
    {
        return view('actividades.form', [
            'actividad' => new Actividad([
                'fecha_inicio' => now()->startOfDay(),
                'fecha_fin' => now()->startOfDay(),
                'zona' => 'ambas',
                'estado' => 'planeada',
                'puntos' => 5,
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
        DB::transaction(function () use ($actividad): void {
            $existe = DB::table('actividades')
                ->where('id_actividad', $actividad)
                ->lockForUpdate()
                ->exists();
            abort_unless($existe, 404);

            if (DB::table('asistencia_voluntarios')->where('id_actividad', $actividad)->exists()) {
                throw ValidationException::withMessages([
                    'actividad' => 'No se puede eliminar una actividad que ya tiene registros de asistencia.',
                ]);
            }

            Actividad::where('id_actividad', $actividad)->delete();
        });

        return redirect()->route('planning.index')->with('success', 'Actividad eliminada correctamente.');
    }

    public function calendario(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $inicio = CarbonImmutable::create((int) $filtros['anio'], (int) $filtros['mes'], 1)->startOfMonth();

        return response()->json([
            'success' => true,
            'actividades' => DB::table('actividades')
                ->whereBetween('fecha_inicio', [$inicio, $inicio->endOfMonth()])
                ->orderBy('fecha_inicio')
                ->get([
                    'id_actividad',
                    'titulo',
                    'descripcion',
                    'fecha_inicio',
                    'fecha_fin',
                    'zona',
                    'estado',
                    'puntos',
                ])
                ->map(fn ($actividad): array => [
                    'id_actividad' => (int) $actividad->id_actividad,
                    'titulo' => $actividad->titulo,
                    'descripcion' => $actividad->descripcion,
                    'fecha_inicio' => $actividad->fecha_inicio,
                    'fecha_fin' => $actividad->fecha_fin,
                    'zona' => $actividad->zona,
                    'estado' => $actividad->estado,
                    'puntos' => (int) $actividad->puntos,
                ]),
        ]);
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
                'puntos' => (int) $actividad->puntos,
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
        $datos['id_responsable'] = ($datos['id_usuario_responsable'] ?? null) ?: $idUsuario;
        unset($datos['id_usuario_responsable']);

        return $datos;
    }

    private function usuariosResponsables()
    {
        return DB::table('usuarios')
            ->where('estatus', 'ACTIVO')
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'email']);
    }
}
