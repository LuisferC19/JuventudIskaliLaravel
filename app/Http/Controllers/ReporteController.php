<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        return view('reportes');
    }

    public function generar(Request $request): View|Response
    {
        $filtros = $request->validate([
            'tipo' => ['required', Rule::in(['donadores', 'beneficiarios', 'campanas', 'donaciones', 'inventario', 'voluntarios'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'formato' => ['nullable', Rule::in(['pdf', 'html'])],
            'region' => ['nullable', Rule::in(['san_martin', 'tlaxcala'])],
            'producto' => ['nullable', 'string', 'max:100'],
        ]);

        $tipo = $filtros['tipo'];
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        $formato = $filtros['formato'] ?? 'pdf';
        $region = $filtros['region'] ?? null;
        $producto = $filtros['producto'] ?? null;
        $query = match ($tipo) {
            'donadores' => $this->donadores(),
            'beneficiarios' => $this->beneficiarios(),
            'campanas' => DB::table('campanas')->select('nombre', 'tipo_meta', 'fecha_inicio', 'fecha_cierre', 'estado', 'meta_cantidad'),
            'donaciones' => $this->donaciones(),
            'inventario' => $this->inventario(),
            'voluntarios' => $this->voluntarios(),
        };

        $campoFecha = match ($tipo) {
            'campanas' => 'fecha_inicio',
            'donaciones' => 'fecha_recepcion',
            'voluntarios' => 'fecha_ingreso',
            default => 'created_at',
        };
        if ($tipo !== 'inventario') {
            if ($desde) {
                $query->whereDate($campoFecha, '>=', $desde);
            }
            if ($hasta) {
                $query->whereDate($campoFecha, '<=', $hasta);
            }
        }
        if ($tipo === 'beneficiarios' && $region) {
            if ($region === 'san_martin') {
                $query->where('c.municipio', 'like', 'San Mart%');
            } else {
                $query->where('c.municipio', 'Tlaxcala');
            }
        }
        if ($tipo === 'voluntarios' && $region) {
            $query->whereIn('v.zona_asignada', [$region, 'ambas']);
        }
        if ($tipo === 'donaciones' && $producto !== null && trim($producto) !== '') {
            $query->where('tb.nombre', 'like', '%'.trim($producto).'%');
        }
        if ($tipo !== 'inventario') {
            $query->orderBy($campoFecha);
        }
        $filas = $query->get();

        if ($tipo === 'donadores') {
            $filas = $filas->map(function ($fila): array {
                $nombre = match ($fila->tipo_donante) {
                    'persona' => trim(($fila->nombre ?? '').' '.($fila->apellido ?? '')),
                    'grupo' => $fila->nombre_grupo ?? '',
                    'organizacion' => $fila->razon_social ?? '',
                    default => 'Anónimo',
                };

                return [
                    'nombre' => $nombre,
                    'email' => $fila->email,
                    'telefono' => $fila->telefono,
                    'tipo' => $fila->tipo_donante,
                    'fecha' => $fila->created_at,
                ];
            });
        } elseif ($tipo === 'beneficiarios') {
            $filas = $filas->map(function ($fila): array {
                $nombre = $fila->tipo_persona === 'fisica'
                    ? trim(($fila->nombre ?? '').' '.($fila->apellido ?? ''))
                    : ($fila->razon_social ?? '');

                return [
                    'nombre' => $nombre,
                    'tipo' => $fila->tipo_persona,
                    'direccion' => $fila->direccion,
                    'telefono' => $fila->telefono,
                    'estado' => $fila->estado,
                    'fecha' => $fila->created_at,
                ];
            });
        } elseif ($tipo === 'donaciones') {
            $filas = $filas->map(function ($fila): array {
                $nombre = match ($fila->tipo_donante) {
                    'persona' => trim(($fila->donador_nombre ?? '').' '.($fila->donador_apellido ?? '')),
                    'grupo' => $fila->nombre_grupo ?? '',
                    'organizacion' => $fila->razon_social ?? '',
                    default => 'Donador anónimo',
                };

                return [
                    'fecha_recepcion' => $fila->fecha_recepcion,
                    'producto' => $fila->producto,
                    'cantidad' => $fila->cantidad,
                    'unidad' => $fila->unidad_medida,
                    'estado' => $fila->estado,
                    'donador' => $nombre,
                    'campana' => $fila->campana ?? '—',
                ];
            });
        } elseif ($tipo === 'inventario') {
            $filas = $filas->map(fn ($fila): array => [
                'producto' => $fila->producto,
                'unidad' => $fila->unidad,
                'recibida' => $fila->recibida,
                'entregada' => $fila->entregada,
                'disponible' => max(0, (float) $fila->recibida - (float) $fila->entregada),
            ]);
        } elseif ($tipo === 'voluntarios') {
            $filas = $filas->map(fn ($fila): array => [
                'nombre' => trim(($fila->nombre ?? '').' '.($fila->apellido ?? '')),
                'zona' => $fila->zona_asignada,
                'fecha_ingreso' => $fila->fecha_ingreso,
                'bolsa_1' => (int) ($fila->puntos_canjeables ?? 0),
                'bolsa_2' => (int) ($fila->puntos_campamento ?? 0),
                'faltas' => (int) ($fila->faltas_injustificadas ?? 0),
            ]);
        } else {
            $filas = $filas->map(fn ($fila): array => (array) $fila);
        }

        if ($formato === 'pdf') {
            $columnas = $filas->first() ? count($filas->first()) : 1;
            $pdf = Pdf::loadView('reportes.pdf', [
                'tipo' => $tipo,
                'filas' => $filas,
                'desde' => $desde,
                'hasta' => $hasta,
                'region' => $region,
                'producto' => $producto,
                'columnas' => $columnas,
            ]);

            return $pdf->setPaper('letter', $columnas > 6 ? 'landscape' : 'portrait')
                ->download("reporte-{$tipo}-".now()->format('Ymd-His').'.pdf');
        }

        return view('reportes.imprimir', [
            'tipo' => $tipo,
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
            'region' => $region,
            'producto' => $producto,
        ]);
    }

    private function donadores(): \Illuminate\Database\Query\Builder
    {
        return DB::table('donadores as d')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->select('d.tipo_donante', 'd.email', 'd.telefono', 'd.created_at')
            ->addSelect('df.nombre', 'df.apellido', 'dm.razon_social', 'dg.nombre_grupo');
    }

    private function beneficiarios(): \Illuminate\Database\Query\Builder
    {
        return DB::table('beneficiarios as b')
            ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('comunidades as c', 'c.id_comunidad', '=', 'b.id_comunidad')
            ->select('b.tipo_persona', 'b.direccion', 'b.telefono', 'b.estado', 'b.created_at', 'c.municipio')
            ->addSelect('bf.nombre', 'bf.apellido', 'bm.razon_social');
    }

    private function donaciones(): \Illuminate\Database\Query\Builder
    {
        return DB::table('donaciones as d')
            ->join('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
            ->join('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
            ->leftJoin('campanas as c', 'c.id_campana', '=', 'd.id_campana')
            ->leftJoin('donadores as do', 'do.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'do.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'do.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'do.id_donador')
            ->select('d.fecha_recepcion', 'd.estado', 'de.cantidad', 'de.unidad_medida', 'tb.nombre as producto')
            ->addSelect('c.nombre as campana', 'do.tipo_donante', 'df.nombre as donador_nombre', 'df.apellido as donador_apellido', 'dm.razon_social', 'dg.nombre_grupo');
    }

    private function inventario(): \Illuminate\Database\Query\Builder
    {
        return DB::table('inventario as i')
            ->join('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'i.id_tipo_bien')
            ->select('tb.nombre as producto', 'tb.unidad_medida as unidad', 'i.cantidad_total_recibida as recibida', 'i.cantidad_total_entregada as entregada')
            ->orderBy('tb.nombre');
    }

    private function voluntarios(): \Illuminate\Database\Query\Builder
    {
        return DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->leftJoin('bolsa_voluntario as b', 'b.id_voluntario', '=', 'v.id_voluntario')
            ->select('v.zona_asignada', 'v.fecha_ingreso', 'u.nombre', 'u.apellido', 'b.puntos_canjeables', 'b.puntos_campamento', 'b.faltas_injustificadas');
    }
}
