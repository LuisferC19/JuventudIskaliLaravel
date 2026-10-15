<?php

namespace App\Http\Controllers;

use App\Http\Requests\BeneficiarioRequest;
use App\Models\Beneficiario;
use App\Models\Comunidad;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BeneficiarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:beneficiarios,ver')->only('index');
        $this->middleware('permiso:beneficiarios,crear')->only('create', 'store');
        $this->middleware('permiso:beneficiarios,editar')->only('edit', 'update');
        $this->middleware('permiso:beneficiarios,eliminar')->only('destroy');
    }

    public function index()
    {
        $beneficiarios = DB::table('beneficiarios as b')
            ->leftJoin('beneficiarios_fisicos as bf', 'bf.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('beneficiarios_morales as bm', 'bm.id_beneficiario', '=', 'b.id_beneficiario')
            ->leftJoin('comunidades as c', 'c.id_comunidad', '=', 'b.id_comunidad')
            ->leftJoin('usuarios as u', 'u.id_usuario', '=', 'b.id_usuario_registrador')
            ->select(
                'b.id_beneficiario',
                'b.tipo_persona',
                'b.telefono',
                'b.estado',
                'b.created_at',
                'c.nombre as comunidad',
                DB::raw("COALESCE(CONCAT_WS(' ', bf.nombre, bf.apellido), bm.razon_social) AS nombre_completo"),
                'bf.nombre',
                'bf.apellido',
                'bf.edad',
                'bf.curp',
                'bm.razon_social',
                'bm.rfc',
                DB::raw("CONCAT_WS(' ', u.nombre, u.apellido) AS registrado_por")
            )
            ->orderByDesc('b.id_beneficiario')
            ->get();

        $conteos = DB::table('beneficiarios')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos")
            ->selectRaw("SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos")
            ->selectRaw("SUM(CASE WHEN tipo_persona = 'fisica' THEN 1 ELSE 0 END) as fisicos")
            ->selectRaw("SUM(CASE WHEN tipo_persona = 'moral' THEN 1 ELSE 0 END) as morales")
            ->first();

        return view('beneficiarios', [
            'beneficiarios' => $beneficiarios,
            'totalBeneficiarios' => (int) $conteos->total,
            'beneficiariosActivos' => (int) $conteos->activos,
            'beneficiariosInactivos' => (int) $conteos->inactivos,
            'beneficiariosFisicos' => (int) $conteos->fisicos,
            'beneficiariosMorales' => (int) $conteos->morales,
        ]);
    }

    public function create()
    {
        return view('beneficiarios_nuevo', [
            'comunidades' => Comunidad::orderBy('nombre')->get(),
        ]);
    }

    public function store(BeneficiarioRequest $request)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos): void {
            $beneficiario = Beneficiario::create([
                'tipo_persona' => $datos['tipo_persona'],
                'id_comunidad' => $datos['id_comunidad'],
                'direccion' => $datos['direccion'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'estado' => $datos['estado'],
                'id_usuario_registrador' => auth()->id(),
            ]);

            $this->guardarSubtipo($beneficiario, $datos);
        });

        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario registrado correctamente.');
    }

    public function edit(string $beneficiario)
    {
        return view('beneficiarios_editar', [
            'beneficiario' => Beneficiario::with(['fisico', 'moral'])->findOrFail($beneficiario),
            'comunidades' => Comunidad::orderBy('nombre')->get(),
        ]);
    }

    public function update(BeneficiarioRequest $request, string $beneficiario)
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $beneficiario): void {
            $registro = Beneficiario::lockForUpdate()->findOrFail($beneficiario);
            $registro->update([
                'tipo_persona' => $datos['tipo_persona'],
                'id_comunidad' => $datos['id_comunidad'],
                'direccion' => $datos['direccion'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'estado' => $datos['estado'],
            ]);
            $registro->fisico()->delete();
            $registro->moral()->delete();
            $this->guardarSubtipo($registro, $datos);
        });

        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario actualizado correctamente.');
    }

    public function destroy(string $beneficiario)
    {
        try {
            DB::transaction(function () use ($beneficiario): void {
                $registro = Beneficiario::lockForUpdate()->findOrFail($beneficiario);
                $entregaIds = DB::table('entregas')
                    ->where('id_beneficiario', $registro->getKey())
                    ->pluck('id_entrega');

                if ($entregaIds->isNotEmpty()) {
                    DB::table('asignaciones_voluntario')->whereIn('id_entrega', $entregaIds)->delete();
                    DB::table('entregas')->whereIn('id_entrega', $entregaIds)->delete();
                }

                DB::table('beneficiario_tipos_apoyo')->where('id_beneficiario', $registro->getKey())->delete();
                if (Schema::hasTable('quejas_sugerencias')) {
                    DB::table('quejas_sugerencias')->where('id_beneficiario', $registro->getKey())->delete();
                }

                $registro->fisico()->delete();
                $registro->moral()->delete();
                $registro->delete();
            });
        } catch (QueryException $exception) {
            Log::error('No se pudo eliminar el beneficiario.', [
                'id_beneficiario' => $beneficiario,
                'exception' => $exception,
            ]);

            return redirect()->route('beneficiarios.index')
                ->with('error', 'No se pudo eliminar el beneficiario porque existen registros relacionados.');
        }

        return redirect()->route('beneficiarios.index')->with('success', 'Beneficiario eliminado correctamente.');
    }

    private function guardarSubtipo(Beneficiario $beneficiario, array $datos): void
    {
        if ($datos['tipo_persona'] === 'fisica') {
            $beneficiario->fisico()->create([
                'nombre' => $datos['nombre'],
                'apellido' => $datos['apellido'],
                'edad' => $datos['edad'] ?? null,
                'curp' => $datos['curp'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            ]);

            return;
        }

        $beneficiario->moral()->create([
            'razon_social' => $datos['razon_social'],
            'rfc' => $datos['rfc'] ?? null,
        ]);
    }
}
