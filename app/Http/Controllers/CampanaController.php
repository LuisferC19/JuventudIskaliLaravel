<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoCampanaRequest;
use App\Http\Requests\CampanaRequest;
use App\Models\Campana;
use App\Services\NotificacionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampanaController extends Controller
{
    private const TRANSICIONES = [
        'borrador' => ['activa'],
        'activa' => ['pausada', 'cerrada'],
        'pausada' => ['activa', 'cancelada'],
        'cerrada' => [],
        'cancelada' => [],
    ];

    public function __construct()
    {
        $this->middleware('permiso:campanas,ver')->only('index');
        $this->middleware('permiso:campanas,crear')->only('create', 'store');
        $this->middleware('permiso:campanas,editar')->only('edit', 'update', 'cambiarEstado');
        $this->middleware('permiso:campanas,eliminar')->only('destroy');
    }

    public function index()
    {
        $campanas = Campana::with('creador')->orderByDesc('id_campana')->get();
        $totalCampanas = $campanas->count();
        $campanasActivas = $campanas->where('estado', 'activa')->count();
        $campanasCerradas = $campanas->where('estado', 'cerrada')->count();
        $avances = DB::table('avances_campana as a')
            ->leftJoin('campanas as c', 'c.id_campana', '=', 'a.id_campana')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'a.id_usuario')
            ->select(
                'a.id_avance',
                'a.descripcion',
                'a.porcentaje_avance',
                'a.monto_recaudado',
                'a.fecha_registro',
                'c.nombre as nombre_campana',
                DB::raw("CONCAT_WS(' ', u.nombre, u.apellido) AS reportado_por")
            )
            ->orderByDesc('a.fecha_registro')
            ->limit(5)
            ->get();

        return view('campanas', compact(
            'campanas',
            'totalCampanas',
            'campanasActivas',
            'campanasCerradas',
            'avances'
        ));
    }

    public function create()
    {
        return view('campanas_nuevo', ['campana' => null]);
    }

    public function store(CampanaRequest $request)
    {
        $datos = $request->validated();
        unset($datos['estado']);
        $datos['estado'] = 'borrador';
        $datos['id_usuario_creador'] = auth()->id();

        Campana::create($datos);

        return redirect()->route('campanas.index')
            ->with('success', 'Campaña creada correctamente.');
    }

    public function edit(string $campana)
    {
        return view('campanas_editar', [
            'campana' => Campana::findOrFail($campana),
        ]);
    }

    public function update(CampanaRequest $request, string $campana)
    {
        $datos = $request->validated();
        unset($datos['estado']);

        DB::transaction(function () use ($campana, $datos): void {
            $registro = Campana::lockForUpdate()->findOrFail($campana);
            $registro->update($datos);
        });

        return redirect()->route('campanas.index')
            ->with('success', 'Campaña actualizada correctamente.');
    }

    public function destroy(string $campana)
    {
        try {
            DB::transaction(function () use ($campana): void {
                $registro = Campana::lockForUpdate()->findOrFail($campana);

                if ($registro->estado !== 'borrador') {
                    throw new \DomainException('Solo se pueden eliminar campañas en borrador.');
                }

                $registro->delete();
            });
        } catch (\DomainException $exception) {
            return redirect()->route('campanas.index')->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            Log::error('No se pudo eliminar la campaña por sus registros relacionados.', [
                'id_campana' => $campana,
                'exception' => $exception,
            ]);

            return redirect()->route('campanas.index')
                ->with('error', 'No se puede eliminar la campaña porque tiene registros relacionados.');
        }

        return redirect()->route('campanas.index')->with('success', 'Campaña eliminada correctamente.');
    }

    public function cambiarEstado(CambiarEstadoCampanaRequest $request, string $campana)
    {
        $datos = $request->validated();

        $resultado = DB::transaction(function () use ($campana, $datos): array {
            $registro = Campana::lockForUpdate()->findOrFail($campana);
            $nuevoEstado = $datos['estado'];

            if (! in_array($nuevoEstado, self::TRANSICIONES[$registro->estado] ?? [], true)) {
                return ['actualizada' => false];
            }

            $registro->estado = $nuevoEstado;
            $registro->save();

            return [
                'actualizada' => true,
                'activar' => $nuevoEstado === 'activa',
                'id' => (int) $registro->id_campana,
                'nombre' => $registro->nombre,
                'fecha_cierre' => $registro->fecha_cierre->format('Y-m-d'),
            ];
        });

        if (! $resultado['actualizada']) {
            return redirect()->route('campanas.index')
                ->with('error', 'La transición solicitada no está permitida para el estado actual.');
        }

        if ($resultado['activar']) {
            $this->notificarCampanaActiva(
                $resultado['id'],
                $resultado['nombre'],
                $resultado['fecha_cierre']
            );
        }

        return redirect()->route('campanas.index')->with('success', 'Estado de campaña actualizado.');
    }

    private function notificarCampanaActiva(int $idCampana, string $nombre, string $fechaCierre): void
    {
        app(NotificacionService::class)->campanaActiva($idCampana, $nombre, $fechaCierre);
    }
}
