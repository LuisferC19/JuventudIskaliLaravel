<?php

namespace App\Http\Middleware;

use App\Services\PermisosUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function __construct(private readonly PermisosUsuario $permisos)
    {
    }

    public function handle(Request $request, Closure $next, string $modulo, ?string $accion = null): Response
    {
        if (! $this->permisos->usuarioPuede($request->user(), $modulo, $accion)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permiso para ver este módulo.'], 403);
            }

            $usuario = $request->user();
            if (! $usuario || ! $usuario->estaActivo() || ! $usuario->tienePermiso('dashboard.view')) {
                abort(403, 'Tu perfil no tiene acceso al panel web. Usa la aplicación móvil o el portal correspondiente.');
            }

            return redirect()->route('dashboard')
                ->with('error', 'No tienes permiso para ver este módulo.');
        }

        return $next($request);
    }
}
