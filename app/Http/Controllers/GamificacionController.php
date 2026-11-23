<?php

namespace App\Http\Controllers;

use App\Http\Requests\CanjeRequest;
use App\Http\Requests\CampamentoRequest;
use App\Http\Requests\PenalizacionRequest;
use App\Http\Requests\PremioRequest;
use App\Services\PuntosService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GamificacionController extends Controller
{
    public function index(Request $request, PuntosService $puntos): View
    {
        $zonasVisibles = $request->user()->zonasVisibles();
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
                'r.folio',
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

        $consultaBolsas = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->leftJoin('bolsa_voluntario as b', 'b.id_voluntario', '=', 'v.id_voluntario')
            ->where('v.activo', true)
            ->orderBy('u.nombre')
            ->orderBy('u.apellido');
        if ($zonasVisibles !== null) {
            $consultaBolsas->whereIn('v.zona_asignada', $zonasVisibles);
        }
        $bolsas = $consultaBolsas->get([
                'v.id_voluntario',
                'v.zona_asignada',
                'u.nombre',
                'u.apellido',
                'b.puntos_canjeables',
                'b.puntos_campamento',
                'b.faltas_injustificadas',
                'b.campamento_bloqueado',
            ])
            ->map(function ($voluntario) use ($puntos): object {
                $voluntario->puntos_canjeables = (int) ($voluntario->puntos_canjeables ?? 0);
                $voluntario->puntos_campamento = (int) ($voluntario->puntos_campamento ?? 0);
                $voluntario->faltas_injustificadas = (int) ($voluntario->faltas_injustificadas ?? 0);
                $voluntario->campamento_bloqueado = (bool) ($voluntario->campamento_bloqueado ?? false);
                $voluntario->campamentos_calificados = $puntos
                    ->campamentosParaVoluntario((int) $voluntario->id_voluntario)
                    ->count();

                return $voluntario;
            });

        $campamentos = DB::table('campamentos')
            ->orderBy('fecha_inicio')
            ->get()
            ->map(function ($campamento) use ($puntos): object {
                $campamento->voluntarios_que_califican = $puntos
                    ->voluntariosQueCalifican((int) $campamento->id_campamento);
                $campamento->total_calificados = $campamento->voluntarios_que_califican->count();

                return $campamento;
            });

        $consultaPenalizaciones = DB::table('penalizaciones_asistencia as p')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'p.id_voluntario')
            ->join('usuarios as uv', 'uv.id_usuario', '=', 'v.id_usuario')
            ->join('actividades as a', 'a.id_actividad', '=', 'p.id_actividad')
            ->leftJoin('usuarios as ur', 'ur.id_usuario', '=', 'p.id_usuario_registra')
            ->orderByDesc('p.fecha');
        if ($zonasVisibles !== null) {
            $consultaPenalizaciones->whereIn('v.zona_asignada', $zonasVisibles);
        }
        $penalizaciones = $consultaPenalizaciones->get([
            'p.*',
            'v.zona_asignada',
            'uv.nombre as voluntario_nombre',
            'uv.apellido as voluntario_apellido',
            'a.titulo as actividad_titulo',
            'ur.nombre as registra_nombre',
            'ur.apellido as registra_apellido',
        ]);

        $consultaAusencias = DB::table('asistencia_voluntarios as av')
            ->join('voluntarios as v', 'v.id_voluntario', '=', 'av.id_voluntario')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->join('actividades as a', 'a.id_actividad', '=', 'av.id_actividad')
            ->where('av.presente', false)
            ->where('a.estado', 'completada')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('penalizaciones_asistencia as p')
                    ->whereColumn('p.id_voluntario', 'av.id_voluntario')
                    ->whereColumn('p.id_actividad', 'av.id_actividad');
            })
            ->orderByDesc('a.fecha_inicio');
        if ($zonasVisibles !== null) {
            $consultaAusencias->whereIn('v.zona_asignada', $zonasVisibles);
        }
        $ausenciasPendientes = $consultaAusencias->get([
            'av.id_asistencia',
            'av.id_voluntario',
            'av.id_actividad',
            'u.nombre as voluntario_nombre',
            'u.apellido as voluntario_apellido',
            'a.titulo as actividad_titulo',
            'a.fecha_inicio',
        ]);

        return view('gamificacion', [
            'donadores' => DB::table('donadores as d')
                ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
                ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
                ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
                ->where('d.activo', true)
                ->orderBy('d.id_donador')
                ->get(['d.id_donador', 'd.tipo_donante', 'df.nombre', 'df.apellido', 'dm.razon_social', 'dg.nombre_grupo'])
                ->map(function ($donador): object {
                    $donador->nombre = match ($donador->tipo_donante) {
                        'persona' => trim(($donador->nombre ?? '').' '.($donador->apellido ?? '')),
                        'organizacion' => $donador->razon_social,
                        'grupo' => $donador->nombre_grupo,
                        default => 'Donador anónimo',
                    };
                    return $donador;
                }),
            'campanasDiploma' => DB::table('campanas')->orderBy('nombre')->get(['id_campana', 'nombre']),
            'niveles' => DB::table('niveles_gamificacion')->orderBy('puntos_minimos')->get(),
            'insignias' => DB::table('insignias')->where('activa', true)->orderBy('nombre')->get(),
            'reconocimientos' => $reconocimientos,
            'bolsas' => $bolsas,
            'campamentos' => $campamentos,
            'penalizaciones' => $penalizaciones,
            'ausenciasPendientes' => $ausenciasPendientes,
            'actividades' => DB::table('actividades')->orderByDesc('fecha_inicio')->get([
                'id_actividad',
                'titulo',
                'fecha_inicio',
            ]),
            'premios' => DB::table('cat_premios')->orderBy('nombre')->get(),
            'canjes' => DB::table('canjes_premios as c')
                ->join('voluntarios as v', 'v.id_voluntario', '=', 'c.id_voluntario')
                ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
                ->join('cat_premios as p', 'p.id_premio', '=', 'c.id_premio')
                ->orderByDesc('c.fecha_solicitud')
                ->get([
                    'c.*',
                    'u.nombre as voluntario_nombre',
                    'u.apellido as voluntario_apellido',
                    'p.nombre as premio_nombre',
                ]),
            'totalNiveles' => DB::table('niveles_gamificacion')->count(),
            'insigniasActivas' => DB::table('insignias')->where('activa', true)->count(),
            'reconocimientosEmitidos' => DB::table('reconocimientos')->count(),
            'donadoresConNivel' => DB::table('donadores')->whereNotNull('id_nivel')->count(),
            'umbralDefecto' => (int) config('iskali.umbral_defecto'),
        ]);
    }

    public function storeCampamento(CampamentoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['id_usuario_creador'] = $request->user()->id_usuario;
        $datos['created_at'] = now();
        DB::table('campamentos')->insert($datos);

        return redirect()->route('gamificacion.index')->with('success', 'Campamento creado correctamente.');
    }

    public function updateCampamento(CampamentoRequest $request, int $campamento): RedirectResponse
    {
        abort_unless(DB::table('campamentos')->where('id_campamento', $campamento)->exists(), 404);
        DB::table('campamentos')
            ->where('id_campamento', $campamento)
            ->update($request->validated());

        return redirect()->route('gamificacion.index')->with('success', 'Campamento actualizado correctamente.');
    }

    public function storePremio(PremioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['activo'] = $request->has('activo') ? $request->boolean('activo') : true;
        $datos['created_at'] = now();
        DB::table('cat_premios')->insert($datos);

        return redirect()->route('gamificacion.index')->with('success', 'Premio creado correctamente.');
    }

    public function updatePremio(PremioRequest $request, int $premio): RedirectResponse
    {
        abort_unless(DB::table('cat_premios')->where('id_premio', $premio)->exists(), 404);
        DB::table('cat_premios')->where('id_premio', $premio)->update($request->validated());

        return redirect()->route('gamificacion.index')->with('success', 'Premio actualizado correctamente.');
    }

    public function destroyPremio(int $premio): RedirectResponse
    {
        abort_unless(DB::table('cat_premios')->where('id_premio', $premio)->exists(), 404);
        DB::table('cat_premios')
            ->where('id_premio', $premio)
            ->update(['activo' => false]);

        return redirect()->route('gamificacion.index')->with('success', 'Premio desactivado correctamente.');
    }

    public function storeCanje(CanjeRequest $request, PuntosService $puntos): RedirectResponse
    {
        $datos = $request->validated();
        try {
            $puntos->canjear(
                (int) $datos['id_voluntario'],
                (int) $datos['id_premio'],
                auth()->id()
            );
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('gamificacion.index')->with('success', 'Canje registrado correctamente.');
    }

    public function entregarCanje(int $canje, PuntosService $puntos): RedirectResponse
    {
        try {
            $puntos->marcarEntregado($canje);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('gamificacion.index')->with('success', 'Canje marcado como entregado.');
    }

    public function storePenalizacion(PenalizacionRequest $request, PuntosService $puntos): RedirectResponse
    {
        $datos = $request->validated();
        $zonasVisibles = $request->user()->zonasVisibles();
        if ($zonasVisibles !== null) {
            $zona = DB::table('voluntarios')
                ->where('id_voluntario', $datos['id_voluntario'])
                ->value('zona_asignada');
            abort_unless(in_array($zona, $zonasVisibles, true), 403);
        }

        try {
            $puntos->penalizar(
                (int) $datos['id_voluntario'],
                (int) $datos['id_actividad'],
                $datos['tipo'],
                $datos['motivo'] ?? null,
                $request->user()->id_usuario
            );
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('gamificacion.index')->with('success', 'Penalización registrada correctamente.');
    }

    public function anularPenalizacion(Request $request, int $penalizacion, PuntosService $puntos): RedirectResponse
    {
        $zonasVisibles = $request->user()->zonasVisibles();
        if ($zonasVisibles !== null) {
            $zona = DB::table('penalizaciones_asistencia as p')
                ->join('voluntarios as v', 'v.id_voluntario', '=', 'p.id_voluntario')
                ->where('p.id_penalizacion', $penalizacion)
                ->value('v.zona_asignada');
            abort_unless(in_array($zona, $zonasVisibles, true), 403);
        }

        try {
            $puntos->anularPenalizacion($penalizacion, $request->user()->id_usuario);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('gamificacion.index')->with('success', 'Penalización anulada y puntos devueltos.');
    }

    public function cancelarCanje(int $canje, PuntosService $puntos): RedirectResponse
    {
        try {
            $puntos->cancelarCanje($canje, auth()->id());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('gamificacion.index')->with('success', 'Canje cancelado y puntos devueltos.');
    }
}
