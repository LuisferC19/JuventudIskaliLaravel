<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportesController extends Controller
{
    public function __construct(private readonly ReporteService $reportes) {}

    public function index(): View
    {
        return view('reportes');
    }

    public function generar(Request $request): View
    {
        $filtros = $request->validate([
            'tipo' => ['required', 'in:donadores,beneficiarios,campanas'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        $tipo = $filtros['tipo'];
        $desde = $filtros['desde'] ?? null;
        $hasta = $filtros['hasta'] ?? null;
        $reporte = $this->reportes->{$tipo}($desde, $hasta);
        $titulos = [
            'donadores' => 'Listado de donadores',
            'beneficiarios' => 'Listado de beneficiarios',
            'campanas' => 'Listado de campañas',
        ];

        return view('reportes_imprimir', [
            'titulo' => $titulos[$tipo],
            'tipo' => $tipo,
            'rows' => $reporte['rows'],
            'summary' => $reporte['summary'],
            'filtros' => compact('desde', 'hasta'),
        ]);
    }
}
