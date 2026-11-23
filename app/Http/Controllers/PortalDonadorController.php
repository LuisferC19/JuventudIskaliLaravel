<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalDonadorController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $donador = DB::table('donadores as d')
            ->leftJoin('niveles_gamificacion as n', 'n.id_nivel', '=', 'd.id_nivel')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->where('d.id_usuario', $usuario->id_usuario)
            ->select('d.*', 'n.nombre as nivel', 'df.nombre', 'df.apellido', 'dm.razon_social', 'dg.nombre_grupo')
            ->first();

        $nombre = $usuario->nombre.' '.$usuario->apellido;
        $tipo = null;
        $nivel = null;
        $puntos = 0;
        $insignias = collect();
        $aportaciones = collect();
        $reconocimientos = collect();

        if ($donador) {
            $tipo = $donador->tipo_donante;
            $nombre = match ($tipo) {
                'persona' => trim(($donador->nombre ?? '').' '.($donador->apellido ?? '')) ?: $nombre,
                'organizacion' => $donador->razon_social ?: $nombre,
                'grupo' => $donador->nombre_grupo ?: $nombre,
                default => 'Donador anónimo',
            };
            $nivel = $donador->nivel;
            $puntos = (int) $donador->puntos_acumulados;
            $insignias = DB::table('donador_insignias as di')
                ->join('insignias as i', 'i.id_insignia', '=', 'di.id_insignia')
                ->where('di.id_donador', $donador->id_donador)
                ->orderByDesc('di.fecha_obtencion')
                ->get(['i.nombre', 'i.descripcion', 'i.imagen_url', 'di.fecha_obtencion']);
            $aportaciones = DB::table('donaciones as d')
                ->join('donaciones_especie as de', 'de.id_donacion', '=', 'd.id_donacion')
                ->join('tipos_bien as tb', 'tb.id_tipo_bien', '=', 'de.id_tipo_bien')
                ->leftJoin('campanas as c', 'c.id_campana', '=', 'd.id_campana')
                ->where('d.id_donador', $donador->id_donador)
                ->orderByDesc('d.fecha_recepcion')
                ->get(['d.fecha_recepcion', 'd.estado', 'de.cantidad', 'de.unidad_medida', 'tb.nombre as producto', 'c.nombre as campana']);
            $reconocimientos = DB::table('reconocimientos')
                ->where('id_donador', $donador->id_donador)
                ->orderByDesc('fecha_emision')
                ->get(['id_reconocimiento', 'folio', 'descripcion', 'fecha_emision']);
        }

        return view('portal_donador', compact(
            'nombre', 'tipo', 'nivel', 'puntos', 'insignias', 'aportaciones', 'reconocimientos', 'donador'
        ));
    }
}
