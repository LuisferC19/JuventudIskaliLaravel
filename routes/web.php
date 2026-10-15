<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeneficiarioController;
use App\Http\Controllers\CampanaController;
use App\Http\Controllers\DonacionController;
use App\Http\Controllers\DonadorController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::view('/registro', 'registro')->name('registro');
Route::view('/recuperar-password', 'recuperar_password')->name('recuperar_password');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::view('/dashboard', 'dashboard')->middleware('permiso:dashboard,ver')->name('dashboard');

    Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario'])->except('show');
    Route::post('usuarios/{usuario}/desbloquear', [UsuarioController::class, 'desbloquear'])->middleware('permiso:usuarios,editar')->name('usuarios.desbloquear');
    Route::resource('donadores', DonadorController::class)->parameters(['donadores' => 'donador'])->except('show');
    Route::resource('beneficiarios', BeneficiarioController::class)->parameters(['beneficiarios' => 'beneficiario'])->except('show');
    Route::resource('donaciones', DonacionController::class)->parameters(['donaciones' => 'donacion']);
    Route::post('donaciones/{donacion}/cambiar-estado', [DonacionController::class, 'cambiarEstado'])->middleware('permiso:donaciones,editar')->name('donaciones.cambiarEstado');

    Route::resource('campanas', CampanaController::class)->parameters(['campanas' => 'campana'])->except('show');
    Route::post('campanas/{campana}/cambiar-estado', [CampanaController::class, 'cambiarEstado'])->middleware('permiso:campanas,editar')->name('campanas.cambiarEstado');
    Route::resource('entregas', EntregaController::class)->parameters(['entregas' => 'entrega']);
    Route::post('entregas/{entrega}/cambiar-estado', [EntregaController::class, 'cambiarEstado'])->middleware('permiso:entregas,editar')->name('entregas.cambiarEstado');
    Route::get('inventario', [InventarioController::class, 'index'])->middleware('permiso:inventario,ver')->name('inventario.index');
    Route::get('inventario/movimientos', [InventarioController::class, 'movimientos'])->middleware('permiso:inventario,ver')->name('inventario.movimientos');
    Route::post('inventario/ajustar', [InventarioController::class, 'ajustar'])->middleware('permiso:inventario,editar')->name('inventario.ajustar');
    Route::post('inventario/revertir-ajuste', [InventarioController::class, 'revertirUltimoAjuste'])->middleware('permiso:inventario,editar')->name('inventario.revertirAjuste');

    foreach ([
        'voluntarios',
        'notificaciones',
        'planning',
        'reportes',
        'respaldos',
        'gamificacion',
    ] as $modulo) {
        Route::view("/{$modulo}", $modulo)->middleware("permiso:{$modulo},ver")->name("{$modulo}.index");
    }

    Route::view('/voluntarios_nuevo', 'voluntarios_nuevo')->middleware('permiso:voluntarios,crear');
    Route::view('/voluntarios_editar', 'voluntarios_editar')->middleware('permiso:voluntarios,editar');
});
