<?php

namespace App\Http\Controllers;

use App\Http\Requests\CanjeRequest;
use App\Http\Requests\PremioRequest;
use App\Services\PuntosService;
use DomainException;
use Illuminate\Http\RedirectResponse;
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

        $bolsas = DB::table('voluntarios as v')
            ->join('usuarios as u', 'u.id_usuario', '=', 'v.id_usuario')
            ->leftJoin('bolsa_voluntario as b', 'b.id_voluntario', '=', 'v.id_voluntario')
            ->where('v.activo', true)
            ->orderBy('u.nombre')
            ->orderBy('u.apellido')
            ->get([
                'v.id_voluntario',
                'v.zona_asignada',
                'u.nombre',
                'u.apellido',
                'b.puntos_canjeables',
                'b.puntos_campamento',
                'b.campamento_bloqueado',
            ])
            ->map(function ($voluntario): object {
                $voluntario->puntos_canjeables = (int) ($voluntario->puntos_canjeables ?? 0);
                $voluntario->puntos_campamento = (int) ($voluntario->puntos_campamento ?? 0);
                $voluntario->campamento_bloqueado = (bool) ($voluntario->campamento_bloqueado ?? false);

                return $voluntario;
            });

        return view('gamificacion', [
            'niveles' => DB::table('niveles_gamificacion')->orderBy('puntos_minimos')->get(),
            'insignias' => DB::table('insignias')->where('activa', true)->orderBy('nombre')->get(),
            'reconocimientos' => $reconocimientos,
            'bolsas' => $bolsas,
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
            'umbral' => (int) config('iskali.umbral_campamento'),
            'totalNiveles' => DB::table('niveles_gamificacion')->count(),
            'insigniasActivas' => DB::table('insignias')->where('activa', true)->count(),
            'reconocimientosEmitidos' => DB::table('reconocimientos')->count(),
            'donadoresConNivel' => DB::table('donadores')->whereNotNull('id_nivel')->count(),
        ]);
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
