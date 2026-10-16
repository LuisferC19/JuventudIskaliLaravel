<?php

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BeneficiarioController;
use App\Http\Controllers\CampanaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonacionController;
use App\Http\Controllers\DonadorController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VoluntarioController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::view('/registro', 'registro')->name('registro');
Route::post('/registro', [AuthController::class, 'register'])->name('registro.store');
Route::view('/recuperar-password', 'recuperar_password')->name('recuperar_password');
Route::post('/recuperar-password', [AuthController::class, 'solicitarRecuperacion'])->name('recuperar_password.solicitar');
Route::get('/recuperar-password/reset', [AuthController::class, 'mostrarRestablecimiento'])->name('recuperar_password.reset');
Route::post('/recuperar-password/reset', [AuthController::class, 'restablecerContrasena'])->name('recuperar_password.actualizar');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permiso:dashboard,ver')->name('dashboard');

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

    Route::resource('voluntarios', VoluntarioController::class)->except('show');
    Route::post('voluntarios/asignaciones', [VoluntarioController::class, 'storeAsignacion'])->name('voluntarios.asignaciones.store');
    Route::patch('voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'updateAsignacion'])->name('voluntarios.asignaciones.update');
    Route::delete('voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'destroyAsignacion'])->name('voluntarios.asignaciones.destroy');
    Route::get('voluntarios/asistencia/{actividad}', [VoluntarioController::class, 'asistencia'])->name('voluntarios.asistencia');
    Route::post('voluntarios/asistencias', [VoluntarioController::class, 'registrarAsistencia'])->name('voluntarios.asistencias.store');
    Route::post('voluntarios/qr/{actividad}', [VoluntarioController::class, 'generarQr'])->name('voluntarios.qr.generar');
    Route::post('voluntarios/validar-qr', [VoluntarioController::class, 'validarQr'])->name('voluntarios.validar_qr');

    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::get('/notificaciones/ultimas', [NotificacionController::class, 'ultimas'])->name('notificaciones.ultimas');
    Route::post('/notificaciones/marcar-todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.marcarTodas');
    Route::post('/notificaciones/{notificacion}/marcar-leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.marcarLeida');

    Route::get('/planning', [ActividadController::class, 'index'])->name('planning.index');
    Route::get('/planning/actividades-mes', [ActividadController::class, 'actividadesMes'])->name('planning.actividadesMes');
    Route::resource('actividades', ActividadController::class)
        ->parameters(['actividades' => 'actividad'])
        ->except('show');
    Route::post('/actividades/{actividad}/estado', [ActividadController::class, 'actualizarEstado'])->name('actividades.actualizarEstado');

    Route::get('/reportes', [ReportesController::class, 'index'])->middleware('permiso:reportes,ver')->name('reportes.index');
    Route::get('/reportes/generar', [ReportesController::class, 'generar'])->middleware('permiso:reportes,ver')->name('reportes.generar');
    Route::get('/respaldos', [BackupController::class, 'index'])->middleware('permiso:respaldos,ver')->name('respaldos.index');
    Route::post('/respaldos/generar', [BackupController::class, 'generar'])->middleware('permiso:respaldos,crear')->name('respaldos.generar');
    Route::post('/respaldos/importar', [BackupController::class, 'importar'])->middleware('permiso:respaldos,editar')->name('respaldos.importar');

    Route::view('/gamificacion', 'gamificacion')->middleware('permiso:gamificacion,ver')->name('gamificacion.index');
});
