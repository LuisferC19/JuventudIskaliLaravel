<?php

namespace App\Http\Controllers;

use App\Http\Requests\DonadorRequest;
use App\Models\Donador;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DonadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:donadores,ver')->only('index');
        $this->middleware('permiso:donadores,crear')->only('create', 'store');
        $this->middleware('permiso:donadores,editar')->only('edit', 'update');
        $this->middleware('permiso:donadores,eliminar')->only('destroy');
    }

    public function index()
    {
        $donadores = DB::table('donadores as d')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->leftJoin('niveles_gamificacion as ng', 'ng.id_nivel', '=', 'd.id_nivel')
            ->select(
                'd.id_donador',
                'd.tipo_donante',
                'd.email',
                'd.telefono',
                'd.puntos_acumulados',
                'd.activo',
                'd.created_at',
                'ng.nombre as nivel',
                'df.nombre',
                'df.apellido',
                'dm.razon_social',
                'dg.nombre_grupo',
                DB::raw("CASE WHEN df.id_donador IS NOT NULL THEN 'fisica' WHEN dm.id_donador IS NOT NULL THEN 'moral' WHEN dg.id_donador IS NOT NULL THEN 'grupo' ELSE d.tipo_donante END AS tipo_persona"),
                DB::raw("CASE WHEN df.id_donador IS NOT NULL THEN CONCAT_WS(' ', df.nombre, df.apellido) WHEN dm.id_donador IS NOT NULL THEN dm.razon_social WHEN dg.id_donador IS NOT NULL THEN dg.nombre_grupo ELSE 'Anónimo' END AS nombre_completo")
            )
            ->orderByDesc('d.id_donador')
            ->get();

        $conteos = DB::table('donadores as d')
            ->leftJoin('donadores_fisicos as df', 'df.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_morales as dm', 'dm.id_donador', '=', 'd.id_donador')
            ->leftJoin('donadores_grupos as dg', 'dg.id_donador', '=', 'd.id_donador')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN d.activo = 1 THEN 1 ELSE 0 END) as activos')
            ->selectRaw('SUM(CASE WHEN df.id_donador IS NOT NULL THEN 1 ELSE 0 END) as fisicos')
            ->selectRaw('SUM(CASE WHEN dm.id_donador IS NOT NULL THEN 1 ELSE 0 END) as morales')
            ->selectRaw('SUM(CASE WHEN dg.id_donador IS NOT NULL THEN 1 ELSE 0 END) as grupos')
            ->selectRaw("SUM(CASE WHEN d.tipo_donante = 'anonimo' THEN 1 ELSE 0 END) as anonimos")
            ->first();

        return view('donadores', [
            'donadores' => $donadores,
            'totalDonadores' => (int) $conteos->total,
            'donadoresActivos' => (int) $conteos->activos,
            'donadoresInactivos' => (int) $conteos->total - (int) $conteos->activos,
            'donadoresFisicos' => (int) $conteos->fisicos,
            'donadoresMorales' => (int) $conteos->morales,
            'donadoresGrupos' => (int) $conteos->grupos,
            'donadoresAnonimos' => (int) $conteos->anonimos,
        ]);
    }

    public function create()
    {
        return view('donadores_nuevo');
    }

    public function store(DonadorRequest $request)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($request, $datos): void {
            $donador = Donador::create([
                'tipo_donante' => $this->tipoDonante($datos['tipo_persona']),
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'puntos_acumulados' => $datos['puntos_acumulados'] ?? 0,
                'activo' => $request->boolean('activo'),
            ]);

            $this->guardarSubtipo($donador, $datos);
        });

        return redirect()->route('donadores.index')->with('success', 'Donador registrado correctamente.');
    }

    public function edit(string $donador)
    {
        return view('donadores_editar', [
            'donador' => Donador::with(['fisico', 'moral', 'grupo'])->findOrFail($donador),
        ]);
    }

    public function update(DonadorRequest $request, string $donador)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($request, $datos, $donador): void {
            $registro = Donador::lockForUpdate()->findOrFail($donador);
            $registro->update([
                'tipo_donante' => $this->tipoDonante($datos['tipo_persona']),
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'puntos_acumulados' => $datos['puntos_acumulados'] ?? 0,
                'activo' => $request->boolean('activo'),
            ]);

            $registro->fisico()->delete();
            $registro->moral()->delete();
            $registro->grupo()->delete();
            $this->guardarSubtipo($registro, $datos);
        });

        return redirect()->route('donadores.index')->with('success', 'Donador actualizado correctamente.');
    }

    public function destroy(string $donador)
    {
        try {
            DB::transaction(function () use ($donador): void {
                $registro = Donador::lockForUpdate()->findOrFail($donador);
                $registro->fisico()->delete();
                $registro->moral()->delete();
                $registro->grupo()->delete();
                $registro->delete();
            });
        } catch (QueryException $exception) {
            Log::error('No se pudo eliminar el donador.', [
                'id_donador' => $donador,
                'exception' => $exception,
            ]);

            return redirect()->route('donadores.index')
                ->with('error', 'No se pudo eliminar el donador porque tiene registros relacionados.');
        }

        return redirect()->route('donadores.index')->with('success', 'Donador eliminado correctamente.');
    }

    private function tipoDonante(string $tipoPersona): string
    {
        return match ($tipoPersona) {
            'fisica' => 'persona',
            'moral' => 'organizacion',
            'grupo' => 'grupo',
            'anonimo' => 'anonimo',
        };
    }

    private function guardarSubtipo(Donador $donador, array $datos): void
    {
        match ($datos['tipo_persona']) {
            'fisica' => $donador->fisico()->create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'curp' => $datos['curp'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            ]),
            'moral' => $donador->moral()->create([
                'razon_social' => $datos['razon_social'],
                'rfc' => $datos['rfc'] ?? null,
                'representante_legal' => $datos['representante_legal'] ?? null,
                'giro_comercial' => $datos['giro_comercial'] ?? null,
            ]),
            'grupo' => $donador->grupo()->create([
                'nombre_grupo' => $datos['nombre_grupo'],
                'representante' => $datos['representante'] ?? null,
            ]),
            'anonimo' => null,
        };
    }
}
