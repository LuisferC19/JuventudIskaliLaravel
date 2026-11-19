<?php

namespace App\View\Composers;

use App\Models\Usuario;
use App\Services\NotificacionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TopbarComposer
{
    public function __construct(private readonly NotificacionService $notificaciones)
    {
    }

    public function compose(View $view): void
    {
        /** @var Usuario $usuario */
        $usuario = Auth::user();
        $nombreCompleto = trim($usuario->nombre.' '.$usuario->apellido);

        $view->with([
            'notificacionesNoLeidas' => $this->notificaciones->contarNoLeidas((int) $usuario->id_usuario),
            'nombreCompleto' => $nombreCompleto,
            'iniciales' => mb_strtoupper(
                mb_substr($usuario->nombre, 0, 1).mb_substr($usuario->apellido, 0, 1)
            ),
        ]);
    }
}
