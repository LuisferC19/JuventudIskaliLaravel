<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntregaRequest;
use App\Services\EntregaWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LogicException;

class EntregaController extends Controller
{
    public function __construct(private readonly EntregaWorkflowService $workflow)
    {
        $this->middleware('permiso:entregas,ver')->only(['index', 'show']);
        $this->middleware('permiso:entregas,crear')->only(['create', 'store']);
        $this->middleware('permiso:entregas,editar')->only(['edit', 'update', 'cambiarEstado']);
        $this->middleware('permiso:entregas,eliminar')->only('destroy');
    }

    public function index(): View
    {
        $entregas = $this->entregasQuery()->orderByDesc('e.id_entrega')->get();
        return view('entregas', [
            'entregas' => $entregas,
            'totalEntregas' => $entregas->count(),
            'entregasProgramadas' => $entregas->where('estado', 'programada')->count(),
            'entregasEnProceso' => $entregas->where('estado', 'en_proceso')->count(),
        ]);
    }

    public function create(): View
    {
        return view('entregas_nuevo', $this->formOptions());
    }

    public function store(EntregaRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['fecha_entrega'] = str_replace('T', ' ', $datos['fecha_entrega']).':00';

        try {
            $this->workflow->crear($datos, (int) $request->user()->getAuthIdentifier());
        } catch (\Throwable $error) {
            Log::error('No se pudo registrar entrega.', ['error' => $error->getMessage()]);
            return back()->withInput()->withErrors(['entrega' => 'No se pudo registrar; verifica que la donación siga verificada y el beneficiario activo.']);
        }

        return redirect()->route('entregas.index')->with('success', 'Entrega registrada correctamente.');
    }

    public function show(string $entrega): View
    {
        $detalle = $this->entregasQuery()->where('e.id_entrega', $entrega)->first();
        abort_unless($detalle, 404);
        return view('entregas_detalle', ['entrega' => $detalle]);
    }

    public function edit(string $entrega): View
    {
        $registro = DB::table('entregas')->where('id_entrega', $entrega)->first();
        abort_unless($registro, 404);
        abort_unless(in_array($registro->estado, ['programada', 'en_proceso'], true), 403, 'Solo se pueden editar entregas programadas o en proceso.');

        return view('entregas_editar', $this->formOptions((int) $registro->id_beneficiario) + [
            'entrega' => $registro,
            'modoEdicion' => true,
        ]);
    }

    public function update(EntregaRequest $request, string $entrega): RedirectResponse
    {
        if ($request->exists('estado')) {
            $datos = $request->validated();
            try {
                $this->workflow->cambiarEstado((int) $entrega, $datos['estado'], (int) $request->user()->getAuthIdentifier());
            } catch (\Throwable $error) {
                Log::error('No se pudo cambiar el estado de entrega.', ['id_entrega' => $entrega, 'error' => $error->getMessage()]);
                return back()->withErrors(['estado' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo cambiar el estado; no se guardaron cambios.']);
            }
            if ($datos['estado'] === 'completada') {
                $this->workflow->notificarCompletada((int) $entrega);
            }
            return redirect()->route('entregas.index')->with('success', 'Estado de entrega actualizado.');
        }

        $datos = $request->validated();
        $datos['fecha_entrega'] = str_replace('T', ' ', $datos['fecha_entrega']).':00';

        try {
            $this->workflow->actualizar((int) $entrega, $datos);
        } catch (\Throwable $error) {
            Log::error('No se pudo editar entrega.', ['id_entrega' => $entrega, 'error' => $error->getMessage()]);
            return back()->withInput()->withErrors(['entrega' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo actualizar la entrega.']);
        }

        return redirect()->route('entregas.index')->with('success', 'Entrega actualizada correctamente.');
    }

    public function cambiarEstado(EntregaRequest $request, string $entrega): RedirectResponse
    {
        return $this->update($request, $entrega);
    }

    public function destroy(string $entrega): RedirectResponse
    {
        try {
            $this->workflow->eliminar((int) $entrega);
        } catch (\Throwable $error) {
            Log::error('No se pudo eliminar entrega.', ['id_entrega' => $entrega, 'error' => $error->getMessage()]);
            return back()->withErrors(['entrega' => $error instanceof LogicException ? $error->getMessage() : 'No se pudo eliminar la entrega.']);
        }

        return redirect()->route('entregas.index')->with('success', 'Entrega eliminada correctamente.');
    }

    private function entregasQuery()
    {
        return DB::table('entregas as e')
            ->join('donaciones as d', 'd.id_donacion', '=', 'e.id_donacion')
            ->join('donadores as dn', 'dn.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'dn.id_donador')
            ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
            ->join('beneficiarios as b', 'b.id_beneficiario', '=', 'e.id_beneficiario')
            ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'e.id_usuario_responsable')
            ->leftJoin('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
            ->leftJoin('donaciones_economicas as deco', 'deco.id_donacion', '=', 'd.id_donacion')
            ->select('e.*', 'd.estado as estado_donacion', 'd.fecha_recepcion', 'c.nombre as campana', 'tb.nombre as tipo_bien')
            ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo, 'Anónimo') AS donador")
            ->selectRaw("COALESCE(bm.razon_social, CONCAT(COALESCE(bf.nombre, ''), ' ', COALESCE(bf.apellido, ''))) AS beneficiario")
            ->selectRaw("CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, '')) AS responsable")
            ->selectRaw("COALESCE(de.unidad_medida, deco.moneda) AS unidad_medida")
            ->selectRaw("COALESCE(tb.nombre, 'Donación económica') AS tipo_donacion");
    }

    private function formOptions(?int $idBeneficiario = null): array
    {
        $beneficiarios = DB::table('beneficiarios as b')
            ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
            ->where(function ($query) use ($idBeneficiario): void {
                $query->where('b.estado', 'activo');
                if ($idBeneficiario !== null) {
                    $query->orWhere('b.id_beneficiario', $idBeneficiario);
                }
            })
            ->select('b.id_beneficiario')
            ->selectRaw("COALESCE(bm.razon_social, CONCAT(COALESCE(bf.nombre, ''), ' ', COALESCE(bf.apellido, ''))) AS nombre")
            ->orderBy('nombre')
            ->get();

        $donaciones = DB::table('donaciones as d')
            ->join('donadores as dn', 'dn.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'dn.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'dn.id_donador')
            ->join('campanas as c', 'c.id_campana', '=', 'd.id_campana')
            ->leftJoin('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->leftJoin('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
            ->leftJoin('donaciones_economicas as deco', 'deco.id_donacion', '=', 'd.id_donacion')
            ->where('d.estado', 'verificada')
            ->select('d.id_donacion')
            ->selectRaw("COALESCE(tb.nombre, 'Donación económica') AS tipo_donacion")
            ->selectRaw("COALESCE(dm.razon_social, CONCAT(COALESCE(df.nombre, ''), ' ', COALESCE(df.apellido, '')), dg.nombre_grupo, 'Anónimo') AS donador")
            ->selectRaw('c.nombre AS campana')
            ->selectRaw('COALESCE(de.cantidad, deco.monto) AS cantidad')
            ->selectRaw('COALESCE(de.unidad_medida, deco.moneda) AS unidad_medida')
            ->orderByDesc('d.id_donacion')
            ->get();

        return compact('donaciones', 'beneficiarios');
    }
}
