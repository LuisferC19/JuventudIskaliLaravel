<?php

namespace App\Http\Controllers;

use App\Http\Requests\DonacionRequest;
use App\Models\Donacion;
use App\Services\DonacionWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LogicException;

class DonacionController extends Controller
{
    public function __construct(private readonly DonacionWorkflowService $workflow)
    {
        $this->middleware('permiso:donaciones,ver')->only(['index', 'show']);
        $this->middleware('permiso:donaciones,crear')->only(['create', 'store']);
        $this->middleware('permiso:donaciones,editar')->only(['edit', 'update', 'cambiarEstado']);
        $this->middleware('permiso:donaciones,eliminar')->only('destroy');
    }

    public function index(Request $request): View
    {
        $donaciones = DB::table('donaciones as d')
            ->join('donadores as dn', 'dn.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'dn.id_donador')
            ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'd.id_usuario_registrador')
            ->leftJoin('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
            ->leftJoin('donaciones_economicas as deco', 'deco.id_donacion', '=', 'd.id_donacion')
            ->select('d.id_donacion', 'd.fecha_recepcion', 'd.estado', 'd.evidencia_url', 'c.nombre as campana')
            ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo, 'Anónimo') AS donador")
            ->selectRaw("CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) AS registrado_por")
            ->selectRaw("CASE WHEN de.id_donacion IS NULL THEN 'economica' ELSE 'especie' END AS tipo")
            ->selectRaw("COALESCE(tb.nombre, 'Donación económica') AS detalle")
            ->selectRaw('COALESCE(de.cantidad, deco.monto) AS cantidad_monto')
            ->selectRaw('COALESCE(de.unidad_medida, deco.moneda) AS unidad_medida')
            ->when($request->filled('estado'), fn ($query) => $query->where('d.estado', $request->input('estado')))
            ->orderByDesc('d.id_donacion')
            ->get();

        return view('donaciones', [
            'donaciones' => $donaciones,
            'filtroEstado' => $request->input('estado', ''),
            'totalDonaciones' => $donaciones->count(),
            'donacionesPendientes' => $donaciones->where('estado', 'pendiente')->count(),
            'donacionesRecibidas' => $donaciones->where('estado', 'recibida')->count(),
            'donacionesVerificadas' => $donaciones->where('estado', 'verificada')->count(),
        ]);
    }

    public function create(): View
    {
        return view('donaciones_nuevo', $this->formOptions());
    }

    public function store(DonacionRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        try {
            $id = $this->workflow->crear($datos, (int) $request->user()->getAuthIdentifier());
        } catch (\Throwable $error) {
            Log::error('No se pudo registrar donación.', ['error' => $error->getMessage()]);
            return back()->withInput()->withErrors(['donacion' => 'No se pudo registrar la donación; revisa que sus referencias sigan activas.']);
        }

        $this->workflow->notificarNueva($id);
        return redirect()->route('donaciones.index')->with('success', 'Donación registrada correctamente.');
    }

    public function show(string $donacion): View
    {
        $detalle = DB::table('donaciones as d')
            ->join('donadores as dn', 'dn.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'dn.id_donador')
            ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
            ->leftJoin('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
            ->leftJoin('donaciones_economicas as deco', 'deco.id_donacion', '=', 'd.id_donacion')
            ->where('d.id_donacion', $donacion)
            ->select('d.*', 'c.nombre as campana', 'de.id_tipo_bien', 'de.descripcion', 'de.cantidad', 'de.unidad_medida', 'tb.nombre as tipo_bien', 'deco.monto', 'deco.moneda', 'deco.metodo_pago', 'deco.referencia_pago')
            ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo, 'Anónimo') AS donador")
            ->selectRaw("CASE WHEN de.id_donacion IS NULL THEN 'economica' ELSE 'especie' END AS tipo")
            ->first();
        abort_unless($detalle, 404);

        return view('donaciones_detalle', ['donacion' => $detalle]);
    }

    public function edit(string $donacion): View
    {
        $registro = Donacion::with(['especie', 'economica'])->findOrFail($donacion);
        abort_unless($registro->estado === 'pendiente', 403, 'Solo se pueden editar donaciones pendientes.');

        return view('donaciones_editar', $this->formOptions() + [
            'donacion' => $registro,
            'modoEdicion' => true,
            'tipoDonacion' => $registro->especie ? 'especie' : 'economica',
        ]);
    }

    public function update(DonacionRequest $request, string $donacion): RedirectResponse
    {
        $registro = Donacion::with(['especie', 'economica'])->findOrFail($donacion);

        if ($request->exists('estado')) {
            $datos = $request->validated();
            try {
                $this->workflow->cambiarEstado((int) $registro->id_donacion, $datos['estado'], (int) $request->user()->getAuthIdentifier());
            } catch (\Throwable $error) {
                Log::error('No se pudo cambiar el estado de donación.', ['id_donacion' => $donacion, 'error' => $error->getMessage()]);
                return back()->withErrors(['estado' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo actualizar el estado; no se guardaron cambios.']);
            }
            if ($datos['estado'] === 'verificada') {
                $this->workflow->notificarVerificada((int) $registro->id_donacion);
            }
            return redirect()->route('donaciones.index')->with('success', 'Estado de donación actualizado.');
        }

        abort_unless($registro->estado === 'pendiente', 403, 'Solo se pueden editar donaciones pendientes.');
        $datos = $request->validated();

        try {
            $this->workflow->actualizar((int) $registro->id_donacion, $datos);
        } catch (\Throwable $error) {
            Log::error('No se pudo editar donación.', ['id_donacion' => $donacion, 'error' => $error->getMessage()]);
            return back()->withInput()->withErrors(['donacion' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo actualizar la donación.']);
        }

        return redirect()->route('donaciones.index')->with('success', 'Donación actualizada correctamente.');
    }

    public function cambiarEstado(DonacionRequest $request, string $donacion): RedirectResponse
    {
        return $this->update($request, $donacion);
    }

    public function destroy(string $donacion): RedirectResponse
    {
        try {
            $this->workflow->eliminar((int) $donacion);
        } catch (\Throwable $error) {
            Log::error('No se pudo eliminar donación.', ['id_donacion' => $donacion, 'error' => $error->getMessage()]);
            return back()->withErrors(['donacion' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo eliminar la donación.']);
        }

        return redirect()->route('donaciones.index')->with('success', 'Donación eliminada correctamente.');
    }

    private function formOptions(): array
    {
        return [
            'donadores' => DB::table('donadores as d')
                ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
                ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
                ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
                ->where('d.activo', true)
                ->select('d.id_donador')
                ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo) AS nombre")
                ->orderBy('nombre')
                ->get(),
            'campanas' => DB::table('campanas')->where('estado', 'activa')->orderBy('nombre')->get(['id_campana', 'nombre']),
            'todasCampanas' => DB::table('campanas')->orderBy('nombre')->get(['id_campana', 'nombre']),
            'tiposBien' => DB::table('tipos_bien')->where('activo', true)->orderBy('nombre')->get(['id_tipo_bien', 'nombre', 'unidad_medida']),
        ];
    }
}
