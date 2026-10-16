<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permiso:notificaciones,ver')->only(['index', 'ultimas']);
        $this->middleware('permiso:notificaciones,editar')->only(['marcarLeida', 'marcarTodas']);
    }

    public function index(Request $request): View
    {
        $consulta = $this->notificacionesWeb((int) $request->user()->getAuthIdentifier());
        $totalNotificaciones = (clone $consulta)->count();
        $noLeidas = (clone $consulta)->where('leida', false)->count();
        $notificaciones = $consulta->orderByDesc('fecha_envio')->get();

        return view('notificaciones', [
            'notificaciones' => $notificaciones,
            'totalNotificaciones' => $totalNotificaciones,
            'noLeidas' => $noLeidas,
        ]);
    }

    public function marcarLeida(Request $request, string $notificacion): JsonResponse
    {
        $actualizadas = Notificacion::query()
            ->where('id_usuario', (int) $request->user()->getAuthIdentifier())
            ->where('id_notificacion', $notificacion)
            ->where('leida', false)
            ->update(['leida' => true]);

        if (! $actualizadas) {
            abort_unless(
                Notificacion::query()
                    ->where('id_usuario', (int) $request->user()->getAuthIdentifier())
                    ->where('id_notificacion', $notificacion)
                    ->exists(),
                404
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
        ]);
    }

    public function marcarTodas(Request $request): JsonResponse
    {
        Notificacion::query()
            ->where('id_usuario', (int) $request->user()->getAuthIdentifier())
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas.',
        ]);
    }

    public function ultimas(Request $request): JsonResponse
    {
        $idUsuario = (int) $request->user()->getAuthIdentifier();

        return response()->json([
            'success' => true,
            'notificaciones' => $this->notificacionesWeb($idUsuario)
                ->where('leida', false)
                ->orderByDesc('fecha_envio')
                ->limit(5)
                ->get([
                    'id_notificacion',
                    'tipo',
                    'asunto',
                    'mensaje',
                    'canal',
                    'fecha_envio',
                ]),
            'total_no_leidas' => $this->notificacionesWeb($idUsuario)
                ->where('leida', false)
                ->count(),
        ]);
    }

    private function notificacionesWeb(int $idUsuario)
    {
        return Notificacion::query()
            ->where('id_usuario', $idUsuario)
            ->where(function ($query): void {
                $query->where('canal', 'web')
                    ->orWhere('canal', 'like', 'web,%')
                    ->orWhere('canal', 'like', '%,web')
                    ->orWhere('canal', 'like', '%,web,%');
            });
    }
}
