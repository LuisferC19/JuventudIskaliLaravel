<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        return view('reportes');
    }

    public function generar(Request $request): View
    {
        $filtros = $request->validate([
            'tipo' => ['required', Rule::in(['donadores', 'beneficiarios', 'campanas'])],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $tipo = $filtros['tipo'];
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        $query = match ($tipo) {
            'donadores' => $this->donadores(),
            'beneficiarios' => $this->beneficiarios(),
            'campanas' => DB::table('campanas')->select('nombre', 'tipo_meta', 'fecha_inicio', 'fecha_cierre', 'estado', 'meta_economica'),
        };

        $campoFecha = $tipo === 'campanas' ? 'fecha_inicio' : 'created_at';
        if ($desde) {
            $query->whereDate($campoFecha, '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate($campoFecha, '<=', $hasta);
        }
        $filas = $query->orderBy($campoFecha)->get();

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
        } else {
            $filas = $filas->map(fn ($fila): array => (array) $fila);
        }

        return view('reportes.imprimir', [
            'tipo' => $tipo,
            'filas' => $filas,
            'desde' => $desde,
            'hasta' => $hasta,
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
            ->select('b.tipo_persona', 'b.direccion', 'b.telefono', 'b.estado', 'b.created_at')
            ->addSelect('bf.nombre', 'bf.apellido', 'bm.razon_social');
    }
}
