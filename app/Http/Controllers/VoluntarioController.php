<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignacionVoluntarioRequest;
use App\Http\Requests\VoluntarioRequest;
use App\Models\Actividad;
use App\Models\Voluntario;
use App\Services\VoluntarioWorkflowService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;

class VoluntarioController extends Controller
{
    public function __construct(private readonly VoluntarioWorkflowService $workflow)
    {
        $this->middleware('permiso:voluntarios,ver')->only(['index', 'asistencia']);
        $this->middleware('permiso:voluntarios,crear')->only(['create', 'store', 'storeAsignacion', 'registrarAsistencia', 'generarQr']);
        $this->middleware('permiso:voluntarios,editar')->only(['edit', 'update', 'updateAsignacion', 'validarQr']);
        $this->middleware('permiso:voluntarios,eliminar')->only(['destroy', 'destroyAsignacion']);
    }

    public function index(): View
    {
        $voluntarios = Voluntario::query()
            ->with('usuario:id_usuario,nombre,apellido,email')
            ->orderByDesc('id_voluntario')
            ->get();

        $asignaciones = DB::table('asignaciones_voluntario as av')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'av.id_voluntario')
            ->join('usuarios as uv', 'uv.id_usuario', '=', 'v.id_usuario')
            ->join('entregas as e', 'e.id_entrega', '=', 'av.id_entrega')
            ->leftJoin('usuarios as ua', 'ua.id_usuario', '=', 'av.id_usuario_asignador')
            ->select(
                'av.*',
                'uv.nombre as nombre_voluntario',
                'uv.apellido as apellido_voluntario',
                'ua.nombre as nombre_asignador',
                'ua.apellido as apellido_asignador',
                'e.estado as estado_entrega',
                'e.fecha_entrega'
            )
            ->orderByDesc('av.id_asignacion')
            ->get();

        $actividades = Actividad::query()
            ->whereIn('estado', ['planeada', 'en_curso'])
            ->orderBy('fecha_inicio')
            ->get();

        $entregasDisponibles = DB::table('entregas')
            ->whereIn('estado', ['programada', 'en_proceso'])
            ->orderByDesc('fecha_entrega')
            ->get();

        return view('voluntarios', [
            'voluntarios' => $voluntarios,
            'asignaciones' => $asignaciones,
            'actividades' => $actividades,
            'entregasDisponibles' => $entregasDisponibles,
            'totalVoluntarios' => $voluntarios->count(),
            'voluntariosActivos' => $voluntarios->where('activo', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('voluntarios_nuevo', $this->opcionesFormulario());
    }

    public function store(VoluntarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activo'] = true;
        Voluntario::create($datos);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario registrado correctamente.');
    }

    public function edit(string $voluntario): View
    {
        return view('voluntarios_editar', $this->opcionesFormulario(
            Voluntario::query()->findOrFail($voluntario)
        ));
    }

    public function update(VoluntarioRequest $request, string $voluntario): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activo'] = $request->boolean('activo');
        Voluntario::query()->findOrFail($voluntario)->update($datos);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario actualizado correctamente.');
    }

    public function destroy(string $voluntario): RedirectResponse
    {
        $registro = Voluntario::query()->findOrFail($voluntario);
        $registro->update(['activo' => false]);

        return redirect()->route('voluntarios.index')->with('success', 'Voluntario desactivado correctamente.');
    }

    public function storeAsignacion(AsignacionVoluntarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['fecha_compromiso'] = Carbon::parse($datos['fecha_compromiso'])->format('Y-m-d H:i:s');
        try {
            $this->workflow->crearAsignacion(
                $datos,
                (int) $request->user()->getAuthIdentifier()
            );
        } catch (LogicException $error) {
            return back()->withInput()->withErrors(['asignacion' => $error->getMessage()]);
        }

        return redirect()->route('voluntarios.index')->with('success', 'Entrega asignada al voluntario.');
    }

    public function updateAsignacion(AsignacionVoluntarioRequest $request, string $asignacion): RedirectResponse
    {
        $datos = $request->validated();
        if (isset($datos['fecha_compromiso'])) {
            $datos['fecha_compromiso'] = Carbon::parse($datos['fecha_compromiso'])->format('Y-m-d H:i:s');
        }
        $this->workflow->actualizarAsignacion((int) $asignacion, $datos);

        return redirect()->route('voluntarios.index')->with('success', 'Asignación actualizada correctamente.');
    }

    public function destroyAsignacion(string $asignacion): RedirectResponse
    {
        $this->workflow->eliminarAsignacion((int) $asignacion);

        return redirect()->route('voluntarios.index')->with('success', 'Asignación eliminada correctamente.');
    }

    public function asistencia(string $actividad): JsonResponse
    {
        $registros = DB::table('asistencia_voluntarios as a')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'a.id_voluntario')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->where('a.id_actividad', $actividad)
            ->select(
                'a.id_asistencia',
                'a.id_voluntario',
                'a.id_actividad',
                'a.fecha_hora',
                'a.metodo',
                'a.presente',
                'v.zona_asignada',
                'u.nombre',
                'u.apellido'
            )
            ->orderBy('u.nombre')
            ->orderBy('u.apellido')
            ->get();

        $registros->each(function ($registro): void {
            $registro->nombre_voluntario = trim($registro->nombre.' '.$registro->apellido);
            unset($registro->nombre, $registro->apellido);
        });

        return response()->json(['success' => true, 'asistencia' => $registros]);
    }

    public function registrarAsistencia(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_voluntario' => ['required', 'integer', 'exists:voluntarios,id_voluntario'],
            'id_actividad' => ['required', 'integer', 'exists:actividades,id_actividad'],
        ], [
            'id_voluntario.required' => 'Selecciona un voluntario.',
            'id_actividad.required' => 'Selecciona una actividad.',
        ]);

        $this->workflow->registrarAsistenciaManual((int) $datos['id_voluntario'], (int) $datos['id_actividad']);

        return response()->json(['success' => true, 'message' => 'Asistencia registrada correctamente.']);
    }

    public function generarQr(Request $request, string $actividad): JsonResponse
    {
        $request->validate([
            'id_actividad' => ['sometimes', 'integer', 'exists:actividades,id_actividad'],
        ]);

        $tokens = $this->workflow->generarTokens((int) $actividad);

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
            'message' => 'Token personal generado para cada voluntario activo.',
        ]);
    }

    public function validarQr(Request $request): JsonResponse
    {
        $token = $request->input('token_qr');
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/i', trim($token))) {
            return response()->json([
                'success' => false,
                'error' => 'El token QR no se proporcionó o no tiene un formato válido.',
            ], 422);
        }

        if (! $this->workflow->validarToken(strtolower(trim($token)))) {
            return response()->json(['success' => false, 'error' => 'Token QR inválido o no vigente.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Asistencia confirmada por QR.']);
    }

    private function opcionesFormulario(?Voluntario $voluntario = null): array
    {
        $usuarios = DB::table('usuarios as u')
            ->whereNotExists(function ($query) use ($voluntario): void {
                $query->selectRaw('1')
                    ->from('voluntarios as v')
                    ->whereColumn('v.id_usuario', 'u.id_usuario');
                if ($voluntario) {
                    $query->where('v.id_voluntario', '<>', $voluntario->id_voluntario);
                }
            })
            ->select('u.id_usuario', 'u.nombre', 'u.apellido', 'u.email')
            ->orderBy('u.nombre')
            ->get();

        return compact('usuarios', 'voluntario');
    }
}
