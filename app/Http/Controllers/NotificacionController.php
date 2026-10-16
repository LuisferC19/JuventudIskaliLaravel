<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function index(Request $request): View
    {
        $notificaciones = $this->notificacionesDelUsuario((int) $request->user()->id_usuario)
            ->orderByDesc('fecha_envio')
            ->get();

        return view('notificaciones', [
            'notificaciones' => $notificaciones,
            'noLeidas' => $notificaciones->where('leida', false)->count(),
        ]);
    }

    public function recientes(Request $request): JsonResponse
    {
        $notificaciones = $this->notificacionesDelUsuario((int) $request->user()->id_usuario)
            ->where('leida', false)
            ->orderByDesc('fecha_envio')
            ->limit(5)
            ->get(['id_notificacion', 'tipo', 'asunto', 'mensaje', 'fecha_envio']);

        return response()->json([
            'success' => true,
            'notificaciones' => $notificaciones,
            'total_no_leidas' => $this->notificacionesDelUsuario((int) $request->user()->id_usuario)
                ->where('leida', false)
                ->count(),
        ]);
    }

    public function ultimas(Request $request): JsonResponse
    {
        return $this->recientes($request);
    }

    public function marcarLeida(Request $request, int $notificacion): RedirectResponse|JsonResponse
    {
        $actualizadas = $this->notificacionesDelUsuario((int) $request->user()->id_usuario)
            ->where('id_notificacion', $notificacion)
            ->where('leida', false)
            ->update(['leida' => true]);

        if ($request->expectsJson()) {
            abort_unless($actualizadas > 0, 404);

            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function marcarTodasLeidas(Request $request): RedirectResponse|JsonResponse
    {
        \Illuminate\Support\Facades\DB::table('notificaciones')
            ->where('id_usuario', (int) $request->user()->id_usuario)
            ->where('leida', false)
            ->update(['leida' => true]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Todas las notificaciones se marcaron como leídas.');
    }

    public function marcarTodas(Request $request): RedirectResponse|JsonResponse
    {
        return $this->marcarTodasLeidas($request);
    }

    private function notificacionesDelUsuario(int $idUsuario): \Illuminate\Database\Query\Builder
    {
        return \Illuminate\Support\Facades\DB::table('notificaciones')
            ->where('id_usuario', $idUsuario)
            ->whereIn('canal', ['web', 'web,app']);
    }
}
