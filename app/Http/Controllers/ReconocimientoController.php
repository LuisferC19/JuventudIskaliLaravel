<?php

namespace App\Http\Controllers;

use App\Services\DiplomaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReconocimientoController extends Controller
{
    public function store(Request $request, DiplomaService $diplomas): RedirectResponse
    {
        $datos = $request->validate([
            'id_donador' => ['required', 'integer', 'exists:donadores,id_donador'],
            'id_campana' => ['nullable', 'integer', 'exists:campanas,id_campana'],
            'descripcion' => ['required', 'string', 'max:255'],
            'otorgado_por' => ['nullable', 'string', 'max:150'],
        ]);

        $id = $diplomas->emitir(
            (int) $datos['id_donador'],
            isset($datos['id_campana']) ? (int) $datos['id_campana'] : null,
            $datos['descripcion'],
            $datos['otorgado_por'] ?? null,
            (int) $request->user()->id_usuario
        );
        $folio = DB::table('reconocimientos')->where('id_reconocimiento', $id)->value('folio');

        return redirect()->route('gamificacion.index')
            ->with('success', "Diploma emitido correctamente. Folio: {$folio}");
    }

    public function descargar(Request $request, int $reconocimiento, DiplomaService $diplomas)
    {
        $registro = DB::table('reconocimientos')->where('id_reconocimiento', $reconocimiento)->first();
        abort_if(! $registro, 404);

        $usuario = $request->user();
        if (! $usuario->tienePermiso('donors.manage') && ! $usuario->tienePermiso('rewards.manage')) {
            $propietario = DB::table('donadores')
                ->where('id_donador', $registro->id_donador)
                ->where('id_usuario', $usuario->id_usuario)
                ->exists();
            abort_unless($propietario, 403);
        }

        return response($diplomas->pdf((int) $reconocimiento), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="diploma-'.$registro->folio.'.pdf"',
        ]);
    }
}
