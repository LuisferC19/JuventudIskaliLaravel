<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignacionVoluntarioRequest;
use App\Services\VoluntarioWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoluntarioController extends Controller
{
    public function index(): View
    {
        $voluntarios = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->orderBy('u.nombre')
            ->orderBy('u.apellido')
            ->get(['v.*', 'u.nombre', 'u.apellido', 'u.email']);

        $actividades = DB::table('actividades')
            ->whereIn('estado', ['planeada', 'en_curso'])
            ->orderBy('fecha_inicio')
            ->get(['id_actividad', 'titulo', 'fecha_inicio']);

        $asignaciones = DB::table('asignaciones_voluntario as a')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'a.id_voluntario')
            ->join('usuarios as uv', 'uv.id_usuario', '=', 'v.id_usuario')
            ->leftJoin('usuarios as ua', 'ua.id_usuario', '=', 'a.id_usuario_asignador')
            ->orderByDesc('a.fecha_asignacion')
            ->get([
                'a.*',
                'uv.nombre as voluntario_nombre',
                'uv.apellido as voluntario_apellido',
                'ua.nombre as asignador_nombre',
                'ua.apellido as asignador_apellido',
            ]);

        return view('voluntarios', compact('voluntarios', 'actividades', 'asignaciones'));
    }

    public function create(): View
    {
        return view('voluntarios_nuevo', [
            'usuariosDisponibles' => $this->usuariosDisponibles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarPerfil($request);
        DB::table('voluntarios')->insert($datos + ['activo' => true]);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario registrado correctamente.');
    }

    public function edit(int $voluntario): View
    {
        $perfil = DB::table('voluntarios')->where('id_voluntario', $voluntario)->first();
        abort_if(!$perfil, 404);

        return view('voluntarios_editar', [
            'voluntario' => $perfil,
            'usuariosDisponibles' => $this->usuariosDisponibles($perfil->id_usuario),
        ]);
    }

    public function update(Request $request, int $voluntario): RedirectResponse
    {
        abort_unless(DB::table('voluntarios')->where('id_voluntario', $voluntario)->exists(), 404);
        $datos = $this->validarPerfil($request, $voluntario);
        $datos['activo'] = $request->boolean('activo');
        DB::table('voluntarios')->where('id_voluntario', $voluntario)->update($datos);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario actualizado correctamente.');
    }

    public function destroy(int $voluntario): RedirectResponse
    {
        $actualizado = DB::table('voluntarios')
            ->where('id_voluntario', $voluntario)
            ->update(['activo' => false]);
        abort_unless($actualizado > 0, 404);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario desactivado correctamente.');
    }

    public function storeAsignacion(AsignacionVoluntarioRequest $request): RedirectResponse
    {
        app(VoluntarioWorkflowService::class)->crearAsignacion(
            $request->validated(),
            (int) $request->user()->id_usuario
        );

        return redirect()->route('voluntarios.index')->with('success', 'Asignación registrada correctamente.');
    }

    public function updateAsignacion(AsignacionVoluntarioRequest $request, int $asignacion): RedirectResponse
    {
        app(VoluntarioWorkflowService::class)->actualizarAsignacion($asignacion, $request->validated());

        return redirect()->route('voluntarios.index')->with('success', 'Asignación actualizada correctamente.');
    }

    public function destroyAsignacion(int $asignacion): RedirectResponse
    {
        app(VoluntarioWorkflowService::class)->eliminarAsignacion($asignacion);

        return redirect()->route('voluntarios.index')->with('success', 'Asignación eliminada correctamente.');
    }

    public function asistencia(int $actividad): JsonResponse
    {
        abort_unless(
            DB::table('actividades')->where('id_actividad', $actividad)->exists(),
            404
        );

        $asistencia = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->leftJoin('asistencia_voluntarios as a', function ($join) use ($actividad): void {
                $join->on('a.id_voluntario', '=', 'v.id_voluntario')
                    ->where('a.id_actividad', '=', $actividad);
            })
            ->where('v.activo', true)
            ->orderBy('u.nombre')
            ->get([
                'a.id_asistencia',
                'v.id_voluntario',
                'u.nombre',
                'u.apellido',
                'v.zona_asignada',
                'a.presente',
                'a.metodo',
            ])
            ->map(fn ($fila): array => [
                'id_asistencia' => $fila->id_asistencia,
                'id_voluntario' => (int) $fila->id_voluntario,
                'nombre_voluntario' => trim($fila->nombre.' '.$fila->apellido),
                'zona_asignada' => $fila->zona_asignada,
                'presente' => (bool) $fila->presente,
                'metodo' => $fila->metodo,
            ]);

        return response()->json(['success' => true, 'asistencia' => $asistencia]);
    }

    public function registrarAsistencia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_voluntario' => ['required', 'integer', 'exists:voluntarios,id_voluntario'],
            'id_actividad' => ['required', 'integer', 'exists:actividades,id_actividad'],
        ]);

        DB::transaction(function () use ($datos): void {
            $voluntario = DB::table('voluntarios')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            $actividad = DB::table('actividades')
                ->where('id_actividad', $datos['id_actividad'])
                ->whereIn('estado', ['planeada', 'en_curso'])
                ->lockForUpdate()
                ->first();

            if (!$voluntario || !$actividad) {
                throw ValidationException::withMessages([
                    'asistencia' => 'El voluntario o la actividad no están activos.',
                ]);
            }

            $registro = DB::table('asistencia_voluntarios')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->where('id_actividad', $datos['id_actividad'])
                ->lockForUpdate()
                ->first();
            if ($registro) {
                DB::table('asistencia_voluntarios')
                    ->where('id_asistencia', $registro->id_asistencia)
                    ->update(['presente' => true, 'metodo' => 'manual', 'token_qr' => null]);

                return;
            }

            DB::table('asistencia_voluntarios')->insert([
                'id_voluntario' => $datos['id_voluntario'],
                'id_actividad' => $datos['id_actividad'],
                'metodo' => 'manual',
                'presente' => true,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Asistencia registrada correctamente.']);
    }

    public function generarQr(Request $request, ?int $actividad = null): JsonResponse
    {
        if ($actividad !== null) {
            return response()->json([
                'success' => true,
                'tokens' => app(VoluntarioWorkflowService::class)->generarTokens($actividad),
            ]);
        }

        $datos = $request->validate([
            'id_voluntario' => ['required', 'integer', 'exists:voluntarios,id_voluntario'],
            'id_actividad' => ['required', 'integer', 'exists:actividades,id_actividad'],
        ]);
        $token = bin2hex(random_bytes(32));

        DB::transaction(function () use ($datos, $token): void {
            $voluntario = DB::table('voluntarios')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->where('activo', true)
                ->lockForUpdate()
                ->first();
            $actividad = DB::table('actividades')
                ->where('id_actividad', $datos['id_actividad'])
                ->whereIn('estado', ['planeada', 'en_curso'])
                ->lockForUpdate()
                ->first();
            if (!$voluntario || !$actividad) {
                throw ValidationException::withMessages([
                    'qr' => 'El voluntario o la actividad no están activos.',
                ]);
            }

            $registro = DB::table('asistencia_voluntarios')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->where('id_actividad', $datos['id_actividad'])
                ->lockForUpdate()
                ->first();
            if ($registro && $registro->presente) {
                throw ValidationException::withMessages([
                    'qr' => 'La asistencia de este voluntario ya está registrada.',
                ]);
            }

            if ($registro) {
                DB::table('asistencia_voluntarios')
                    ->where('id_asistencia', $registro->id_asistencia)
                    ->update(['metodo' => 'qr', 'token_qr' => $token]);
            } else {
                DB::table('asistencia_voluntarios')->insert([
                    'id_voluntario' => $datos['id_voluntario'],
                    'id_actividad' => $datos['id_actividad'],
                    'metodo' => 'qr',
                    'presente' => false,
                    'token_qr' => $token,
                ]);
            }
        });

        return response()->json(['success' => true, 'token' => $token]);
    }

    public function validarQr(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token_qr' => ['required', 'string', 'size:64'],
        ]);
        $registrado = app(VoluntarioWorkflowService::class)->validarToken($datos['token_qr']);

        if (! $registrado) {
            return response()->json([
                'success' => false,
                'error' => 'El código QR no es válido o ya fue utilizado.',
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'Asistencia confirmada por QR.']);
    }

    public function generarQrActividad(int $actividad): JsonResponse
    {
        return response()->json([
            'success' => true,
            'tokens' => app(VoluntarioWorkflowService::class)->generarTokens($actividad),
        ]);
    }

    private function usuariosDisponibles(?int $idActual = null): \Illuminate\Support\Collection
    {
        return DB::table('usuarios')
            ->where('activo', true)
            ->where(function ($query) use ($idActual): void {
                $query->whereNotIn('id_usuario', DB::table('voluntarios')->select('id_usuario'));
                if ($idActual !== null) {
                    $query->orWhere('id_usuario', $idActual);
                }
            })
            ->orderBy('nombre')
            ->get(['id_usuario', 'nombre', 'apellido', 'email']);
    }

    private function validarPerfil(Request $request, ?int $idActual = null): array
    {
        $unicoUsuario = Rule::unique('voluntarios', 'id_usuario');
        if ($idActual !== null) {
            $unicoUsuario->ignore($idActual, 'id_voluntario');
        }

        return $request->validate([
            'id_usuario' => [
                'required',
                'integer',
                'exists:usuarios,id_usuario',
                $unicoUsuario,
            ],
            'telefono_contacto' => ['nullable', 'string', 'max:20'],
            'zona_asignada' => ['required', Rule::in(['san_martin', 'tlaxcala', 'ambas', 'otra'])],
            'disponibilidad' => ['required', Rule::in(['tiempo_completo', 'medio_tiempo', 'fines_semana', 'bajo_demanda'])],
            'fecha_ingreso' => ['required', 'date_format:Y-m-d'],
            'notas' => ['nullable', 'string'],
        ]);
    }
}
