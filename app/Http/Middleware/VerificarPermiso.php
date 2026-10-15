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

    public function handle(Request $request, Closure $next, string $modulo, string $accion): Response
    {
        if (! $this->permisos->usuarioPuede($request->user(), $modulo, $accion)) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes permiso para ver este módulo.');
        }

        return $next($request);
    }
}
