<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarInventarioRequest;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
        $this->middleware('permiso:inventario,ver')->only('index', 'movimientos');
        $this->middleware('permiso:inventario,editar')->only('ajustar');
    }

    public function index()
    {
        return view('inventario', [
            'inventario' => $this->listarInventario(),
            'movimientos' => $this->listarMovimientos(20),
            'historial' => false,
        ]);
    }

    public function movimientos(Request $request)
    {
        $filtro = $request->validate([
            'tipo_bien' => ['nullable', 'integer', 'min:1', 'exists:tipos_bien,id_tipo_bien'],
        ]);

        return view('inventario', [
            'inventario' => $this->listarInventario(),
            'movimientos' => $this->listarMovimientos(null, $filtro['tipo_bien'] ?? null),
            'historial' => true,
            'tipoBienSeleccionado' => $filtro['tipo_bien'] ?? '',
        ]);
    }

    public function ajustar(AjustarInventarioRequest $request)
    {
        $datos = $request->validated();

        if ((float) $datos['cantidad_total_entregada'] > (float) $datos['cantidad_total_recibida']) {
            throw ValidationException::withMessages([
                'cantidad_total_entregada' => 'La cantidad entregada no puede superar la recibida.',
            ]);
        }

        $registrado = $this->inventarioService->registrarAjusteManual(
            (int) $datos['id_tipo_bien'],
            (float) $datos['cantidad_total_recibida'],
            (float) $datos['cantidad_total_entregada'],
            (int) $request->user()->getAuthIdentifier(),
            $datos['motivo']
        );

        if (! $registrado) {
            return back()->withInput()->withErrors([
                'inventario' => 'No se pudo registrar el ajuste. Revisa las cantidades y el motivo.',
            ]);
        }

        return redirect()->route('inventario.index')->with('success', 'Ajuste de inventario registrado correctamente.');
    }

    private function listarInventario()
    {
        return DB::table('tipos_bien as tb')
            ->leftJoin('inventario as i', 'i.id_tipo_bien', '=', 'tb.id_tipo_bien')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'i.id_usuario_actualizador')
            ->select([
                'tb.id_tipo_bien',
                'tb.nombre as tipo_bien',
                'tb.unidad_medida',
                DB::raw('COALESCE(i.cantidad_total_recibida, 0) as cantidad_total_recibida'),
                DB::raw('COALESCE(i.cantidad_total_entregada, 0) as cantidad_total_entregada'),
                DB::raw('COALESCE(i.cantidad_disponible, 0) as cantidad_disponible'),
                'i.ultima_actualizacion',
                DB::raw("TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))) as actualizado_por"),
            ])
            ->orderBy('tb.nombre')
            ->get();
    }

    private function listarMovimientos(?int $limite = null, ?int $idTipoBien = null)
    {
        $consulta = DB::table('movimientos_inventario as mi')
            ->join('inventario as i', 'i.id_inventario', '=', 'mi.id_inventario')
            ->join('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'i.id_tipo_bien')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'mi.id_usuario')
            ->select([
                'mi.id_movimiento',
                'mi.id_inventario',
                'mi.tipo_movimiento',
                'mi.cantidad',
                'mi.cantidad_anterior',
                'mi.cantidad_posterior',
                'mi.tipo_referencia',
                'mi.id_referencia',
                'mi.motivo',
                'mi.fecha',
                'tb.id_tipo_bien',
                'tb.nombre as tipo_bien',
                'tb.unidad_medida',
                DB::raw("TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))) as usuario"),
            ]);

        if ($idTipoBien !== null) {
            $consulta->where('tb.id_tipo_bien', $idTipoBien);
        }

        $movimientos = $consulta->orderByDesc('mi.fecha')
            ->orderByDesc('mi.id_movimiento')
            ->when($limite !== null, fn ($query) => $query->limit($limite))
            ->get();

        return $movimientos->map(function ($movimiento) {
            return $movimiento;
        });
    }
}
