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
use App\Http\Controllers\GamificacionController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RespaldoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VoluntarioController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.authenticate');
Route::get('/registro', [AuthController::class, 'registro'])->name('registro');
Route::post('/registro', [AuthController::class, 'crearCuenta'])->name('registro.store');
Route::get('/recuperar-password', [AuthController::class, 'recuperarPassword'])->name('recuperar_password');
Route::post('/recuperar-password', [AuthController::class, 'solicitarRecuperacion'])->name('recuperar_password.store');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permiso:dashboard,ver')->name('dashboard');
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->middleware('permiso:notificaciones,ver')->name('notificaciones.index');
    Route::get('/notificaciones/recientes', [NotificacionController::class, 'recientes'])->middleware('permiso:notificaciones,ver')->name('notificaciones.recientes');
    Route::post('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->middleware('permiso:notificaciones,editar')->name('notificaciones.leer');
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->middleware('permiso:notificaciones,editar')->name('notificaciones.leer-todas');
    Route::get('/reportes', [ReporteController::class, 'index'])->middleware('permiso:reportes,ver')->name('reportes.index');
    Route::get('/reportes/generar', [ReporteController::class, 'generar'])->middleware('permiso:reportes,ver')->name('reportes.generar');
    Route::get('/respaldos', [RespaldoController::class, 'index'])->middleware('permiso:respaldos,ver')->name('respaldos.index');
    Route::post('/respaldos/generar', [RespaldoController::class, 'generar'])->middleware('permiso:respaldos,crear')->name('respaldos.generar');
    Route::post('/respaldos/importar', [RespaldoController::class, 'importar'])->middleware('permiso:respaldos,editar')->name('respaldos.importar');
    Route::get('/planning', [PlanningController::class, 'index'])->middleware('permiso:planning,ver')->name('planning.index');
    Route::get('/planning/calendario', [PlanningController::class, 'calendario'])->middleware('permiso:planning,ver')->name('planning.calendario');
    Route::post('/planning', [PlanningController::class, 'store'])->middleware('permiso:planning,crear')->name('planning.store');
    Route::put('/planning/{actividad}', [PlanningController::class, 'update'])->middleware('permiso:planning,editar')->name('planning.update');
    Route::delete('/planning/{actividad}', [PlanningController::class, 'destroy'])->middleware('permiso:planning,eliminar')->name('planning.destroy');
    Route::get('/voluntarios', [VoluntarioController::class, 'index'])->middleware('permiso:voluntarios,ver')->name('voluntarios.index');
    Route::get('/voluntarios/create', [VoluntarioController::class, 'create'])->middleware('permiso:voluntarios,crear')->name('voluntarios.create');
    Route::post('/voluntarios', [VoluntarioController::class, 'store'])->middleware('permiso:voluntarios,crear')->name('voluntarios.store');
    Route::get('/voluntarios/{voluntario}/edit', [VoluntarioController::class, 'edit'])->middleware('permiso:voluntarios,editar')->name('voluntarios.edit');
    Route::put('/voluntarios/{voluntario}', [VoluntarioController::class, 'update'])->middleware('permiso:voluntarios,editar')->name('voluntarios.update');
    Route::delete('/voluntarios/{voluntario}', [VoluntarioController::class, 'destroy'])->middleware('permiso:voluntarios,eliminar')->name('voluntarios.destroy');
    Route::post('/voluntarios/asignaciones', [VoluntarioController::class, 'storeAsignacion'])->middleware('permiso:voluntarios,crear')->name('voluntarios.asignaciones.store');
    Route::patch('/voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'updateAsignacion'])->middleware('permiso:voluntarios,editar')->name('voluntarios.asignaciones.update');
    Route::delete('/voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'destroyAsignacion'])->middleware('permiso:voluntarios,eliminar')->name('voluntarios.asignaciones.destroy');
    Route::get('/voluntarios/actividades/{actividad}/asistencia', [VoluntarioController::class, 'asistencia'])->middleware('permiso:voluntarios,ver')->name('voluntarios.asistencia');
    Route::post('/voluntarios/asistencia', [VoluntarioController::class, 'registrarAsistencia'])->middleware('permiso:voluntarios,crear')->name('voluntarios.asistencia.registrar');
    Route::post('/voluntarios/qr', [VoluntarioController::class, 'generarQr'])->middleware('permiso:voluntarios,crear')->name('voluntarios.qr.generar');
    Route::post('/voluntarios/actividades/{actividad}/qr', [VoluntarioController::class, 'generarQrActividad'])->middleware('permiso:voluntarios,crear')->name('voluntarios.actividades.qr.generar');
    Route::post('/voluntarios/qr/validar', [VoluntarioController::class, 'validarQr'])->middleware('permiso:voluntarios,editar')->name('voluntarios.qr.validar');
    Route::get('/gamificacion', [GamificacionController::class, 'index'])->middleware('permiso:gamificacion,ver')->name('gamificacion.index');

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

});
