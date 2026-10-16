<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanningController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'mes' => ['sometimes', 'integer', 'between:1,12'],
            'anio' => ['sometimes', 'integer', 'between:2000,2100'],
        ]);
        $fecha = Carbon::createFromDate(
            (int) ($filtros['anio'] ?? now()->year),
            (int) ($filtros['mes'] ?? now()->month),
            1
        );
        $inicio = $fecha->copy()->startOfMonth();
        $fin = $fecha->copy()->endOfMonth();
        $actividades = DB::table('actividades as a')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'a.id_responsable')
            ->whereBetween('a.fecha_inicio', [$inicio, $fin])
            ->orderBy('a.fecha_inicio')
            ->get(['a.*', 'u.nombre as responsable_nombre', 'u.apellido as responsable_apellido']);

        $semanaInicio = now()->startOfWeek();
        $semanaFin = now()->endOfWeek();

        return view('planning', [
            'fechaCalendario' => $fecha,
            'actividades' => $actividades,
            'totalActividades' => DB::table('actividades')->count(),
            'actividadesSemana' => DB::table('actividades')
                ->whereBetween('fecha_inicio', [$semanaInicio, $semanaFin])
                ->count(),
            'actividadesSanMartin' => DB::table('actividades')->whereIn('zona', ['san_martin', 'ambas'])->count(),
            'actividadesTlaxcala' => DB::table('actividades')->whereIn('zona', ['tlaxcala', 'ambas'])->count(),
            'responsables' => DB::table('usuarios')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id_usuario', 'nombre', 'apellido']),
        ]);
    }

    public function calendario(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2000,2100'],
        ]);
        $inicio = Carbon::createFromDate((int) $filtros['anio'], (int) $filtros['mes'], 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        return response()->json([
            'success' => true,
            'actividades' => DB::table('actividades')
                ->whereBetween('fecha_inicio', [$inicio, $fin])
                ->orderBy('fecha_inicio')
                ->get([
                    'id_actividad',
                    'titulo',
                    'descripcion',
                    'fecha_inicio',
                    'fecha_fin',
                    'zona',
                    'estado',
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarActividad($request);
        $datos['id_responsable'] = $request->user()->id_usuario;
        $datos['creado_en'] = now();
        DB::table('actividades')->insert($datos);

        return redirect()->route('planning.index', [
            'mes' => Carbon::parse($datos['fecha_inicio'])->month,
            'anio' => Carbon::parse($datos['fecha_inicio'])->year,
        ])->with('success', 'Actividad creada correctamente.');
    }

    public function update(Request $request, int $actividad): RedirectResponse
    {
        $datos = $this->validarActividad($request);
        $actualizada = DB::table('actividades')->where('id_actividad', $actividad)->update($datos);
        abort_unless($actualizada > 0 || DB::table('actividades')->where('id_actividad', $actividad)->exists(), 404);

        return redirect()->route('planning.index', [
            'mes' => Carbon::parse($datos['fecha_inicio'])->month,
            'anio' => Carbon::parse($datos['fecha_inicio'])->year,
        ])->with('success', 'Actividad actualizada correctamente.');
    }

    public function destroy(int $actividad): RedirectResponse
    {
        $eliminada = DB::transaction(function () use ($actividad): bool {
            $existe = DB::table('actividades')->where('id_actividad', $actividad)->lockForUpdate()->exists();
            abort_unless($existe, 404);

            if (DB::table('asistencia_voluntarios')->where('id_actividad', $actividad)->exists()) {
                throw ValidationException::withMessages([
                    'actividad' => 'No se puede eliminar una actividad que ya tiene registros de asistencia.',
                ]);
            }

            return DB::table('actividades')->where('id_actividad', $actividad)->delete() > 0;
        });

        return redirect()->route('planning.index')->with(
            $eliminada ? 'success' : 'error',
            $eliminada ? 'Actividad eliminada correctamente.' : 'No se pudo eliminar la actividad.'
        );
    }

    private function validarActividad(Request $request): array
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'zona' => ['required', Rule::in(['san_martin', 'tlaxcala', 'ambas'])],
            'estado' => ['required', Rule::in(['planeada', 'en_curso', 'completada', 'cancelada'])],
        ]);

        $datos['fecha_inicio'] = Carbon::parse($datos['fecha_inicio'])->format('Y-m-d H:i:s');
        $datos['fecha_fin'] = Carbon::parse($datos['fecha_fin'])->format('Y-m-d H:i:s');

        return $datos;
    }
}
