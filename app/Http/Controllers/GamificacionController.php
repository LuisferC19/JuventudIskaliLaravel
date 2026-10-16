<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GamificacionController extends Controller
{
    public function index(): View
    {
        $reconocimientos = DB::table('reconocimientos as r')
            ->leftJoin('donadores as d', 'd.id_donador', '=', 'r.id_donador')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->leftJoin('usuarios as ur', 'ur.id_usuario', '=', 'r.id_usuario')
            ->leftJoin('usuarios as ue', 'ue.id_usuario', '=', 'r.id_usuario_emisor')
            ->leftJoin('campanas as c', 'c.id_campana', '=', 'r.id_campana')
            ->orderByDesc('r.fecha_emision')
            ->get([
                'r.id_reconocimiento',
                'r.tipo',
                'r.descripcion',
                'r.archivo_pdf_url',
                'r.fecha_emision',
                'df.nombre as donador_nombre',
                'df.apellido as donador_apellido',
                'dm.razon_social',
                'dg.nombre_grupo',
                'ur.nombre as usuario_nombre',
                'ur.apellido as usuario_apellido',
                'c.nombre as campana',
                'ue.nombre as emisor_nombre',
                'ue.apellido as emisor_apellido',
            ])
            ->map(function ($reconocimiento): object {
                $receptor = $reconocimiento->razon_social
                    ?? $reconocimiento->nombre_grupo
                    ?? trim(($reconocimiento->donador_nombre ?? '').' '.($reconocimiento->donador_apellido ?? ''));
                if ($receptor === null || trim($receptor) === '') {
                    $receptor = trim(($reconocimiento->usuario_nombre ?? '').' '.($reconocimiento->usuario_apellido ?? ''));
                }
                $reconocimiento->receptor = $receptor ?: '—';
                $reconocimiento->emisor = trim(($reconocimiento->emisor_nombre ?? '').' '.($reconocimiento->emisor_apellido ?? ''));

                return $reconocimiento;
            });

        return view('gamificacion', [
            'niveles' => DB::table('niveles_gamificacion')->orderBy('puntos_minimos')->get(),
            'insignias' => DB::table('insignias')->where('activa', true)->orderBy('nombre')->get(),
            'reconocimientos' => $reconocimientos,
            'totalNiveles' => DB::table('niveles_gamificacion')->count(),
            'insigniasActivas' => DB::table('insignias')->where('activa', true)->count(),
            'reconocimientosEmitidos' => DB::table('reconocimientos')->count(),
            'donadoresConNivel' => DB::table('donadores')->whereNotNull('id_nivel')->count(),
        ]);
    }
}
