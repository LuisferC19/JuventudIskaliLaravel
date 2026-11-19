<?php

namespace App\Providers;

use App\Contracts\InventarioServiceInterface;
use App\Services\InventarioService;
use App\View\Composers\TopbarComposer;
use App\Services\PermisosUsuario;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PermisosUsuario::class);
        $this->app->bind(InventarioServiceInterface::class, InventarioService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('partials.topbar', TopbarComposer::class);

        \Illuminate\Support\Facades\Gate::define(
            'permiso',
            fn ($usuario, string $modulo, ?string $accion = null): bool => app(PermisosUsuario::class)
                ->usuarioPuede($usuario, $modulo, $accion)
        );
    }
}
