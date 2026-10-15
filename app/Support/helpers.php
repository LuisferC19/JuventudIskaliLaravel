<?php

use App\Services\PermisosUsuario;
use Illuminate\Support\Facades\Auth;

if (! function_exists('usuarioPuede')) {
    function usuarioPuede(string $modulo, string $accion): bool
    {
        return app(PermisosUsuario::class)->usuarioPuede(Auth::user(), $modulo, $accion);
    }
}
