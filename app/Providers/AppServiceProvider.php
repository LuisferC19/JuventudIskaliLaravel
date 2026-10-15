<?php

namespace App\Providers;

use App\Contracts\InventarioService as InventarioServiceContract;
use App\Services\InventarioService;
use App\Services\PermisosUsuario;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PermisosUsuario::class);
        $this->app->bind(InventarioServiceContract::class, InventarioService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::define(
            'permiso',
            fn ($usuario, string $modulo, string $accion): bool => app(PermisosUsuario::class)
                ->usuarioPuede($usuario, $modulo, $accion)
        );
    }
}
