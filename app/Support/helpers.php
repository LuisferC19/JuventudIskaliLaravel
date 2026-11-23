<?php

use App\Services\PermisosUsuario;
use Illuminate\Support\Facades\Auth;

if (! function_exists('imagen_base64')) {
    function imagen_base64(string $rutaPublica): string
    {
        $ruta = public_path(ltrim($rutaPublica, '/'));
        if (! is_file($ruta)) {
            return '';
        }

        $tipo = mime_content_type($ruta) ?: 'application/octet-stream';

        return 'data:'.$tipo.';base64,'.base64_encode((string) file_get_contents($ruta));
    }
}

if (! function_exists('usuarioPuede')) {
    function usuarioPuede(string $modulo, ?string $accion = null): bool
    {
        return app(PermisosUsuario::class)->usuarioPuede(Auth::user(), $modulo, $accion);
    }
}
