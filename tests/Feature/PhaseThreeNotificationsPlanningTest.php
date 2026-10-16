<?php

namespace Tests\Feature;

use App\Http\Controllers\ActividadController;
use App\Http\Controllers\NotificacionController;
use App\Models\Usuario;
use App\Services\NotificacionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseThreeNotificationsPlanningTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        $this->crearEsquemaAislado();
        $this->registrarRutasPrueba();
    }

    public function test_dropdown_retorna_solo_las_cinco_no_leidas_del_usuario_actual(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            DB::table('notificaciones')->insert([
                'id_usuario' => 10,
                'tipo' => 'sistema',
                'canal' => 'web',
                'asunto' => "Asunto {$i}",
                'mensaje' => "Mensaje {$i}",
            ]);
        }
        DB::table('notificaciones')->insert([
            'id_usuario' => 20,
            'tipo' => 'sistema',
            'canal' => 'web',
            'asunto' => 'Privada',
            'mensaje' => 'No debe aparecer',
        ]);

        $usuario = new Usuario;
        $usuario->forceFill(['id_usuario' => 10]);
        $this->withoutMiddleware()
            ->actingAs($usuario)
            ->getJson('/__phase3/notifications/latest')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_no_leidas', 6)
            ->assertJsonCount(5, 'notificaciones')
            ->assertJsonMissing(['asunto' => 'Privada']);
    }

    public function test_marcar_leida_y_todas_respetan_la_propiedad_de_cada_notificacion(): void
    {
        DB::table('notificaciones')->insert([
            ['id_notificacion' => 1, 'id_usuario' => 10, 'tipo' => 'sistema', 'canal' => 'web', 'asunto' => 'Una', 'mensaje' => 'Uno'],
            ['id_notificacion' => 2, 'id_usuario' => 10, 'tipo' => 'sistema', 'canal' => 'web', 'asunto' => 'Dos', 'mensaje' => 'Dos'],
            ['id_notificacion' => 3, 'id_usuario' => 20, 'tipo' => 'sistema', 'canal' => 'web', 'asunto' => 'Otra', 'mensaje' => 'Otra'],
            ['id_notificacion' => 4, 'id_usuario' => 10, 'tipo' => 'sistema', 'canal' => 'app', 'asunto' => 'App', 'mensaje' => 'App'],
        ]);
        $usuario = new Usuario;
        $usuario->forceFill(['id_usuario' => 10]);

        $this->withoutMiddleware()
            ->actingAs($usuario)
            ->postJson('/__phase3/notifications/1/read')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 1, 'leida' => true]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 3, 'leida' => false]);

        $this->postJson('/__phase3/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 2, 'leida' => true]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 4, 'leida' => true]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 3, 'leida' => false]);
    }

    public function test_creacion_de_actividad_aplica_fecha_fin_y_el_calendario_entrega_json_agrupado(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 10,
            'nombre' => 'Ana',
            'apellido' => 'López',
            'email' => 'ana@example.test',
            'activo' => true,
        ]);
        $usuario = new Usuario;
        $usuario->forceFill(['id_usuario' => 10]);

        $this->withoutMiddleware()
            ->actingAs($usuario)
            ->post('/__phase3/activities', [
                'titulo' => 'Jornada comunitaria',
                'descripcion' => 'Actividad local',
                'fecha_inicio' => '2026-10-12 09:00:00',
                'fecha_fin' => '',
                'zona' => 'tlaxcala',
                'estado' => 'planeada',
                'id_usuario_responsable' => '',
            ])
            ->assertRedirect(route('planning.index', ['mes' => 10, 'anio' => 2026]));

        $actividad = DB::table('actividades')->first();
        $this->assertSame('2026-10-12 09:00:00', $actividad->fecha_inicio);
        $this->assertSame('2026-10-12 09:00:00', $actividad->fecha_fin);
        $this->assertSame(10, (int) $actividad->id_responsable);

        $this->getJson('/__phase3/calendar?mes=10&anio=2026')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('actividades.12.0.titulo', 'Jornada comunitaria')
            ->assertJsonPath('actividades.12.0.nombre', 'Ana');
    }

    public function test_actividad_se_puede_actualizar_cambiar_de_estado_y_eliminar(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 10,
            'nombre' => 'Ana',
            'apellido' => 'López',
            'email' => 'ana@example.test',
            'activo' => true,
        ]);
        DB::table('actividades')->insert([
            'id_actividad' => 5,
            'titulo' => 'Actividad inicial',
            'fecha_inicio' => '2026-10-12 09:00:00',
            'fecha_fin' => '2026-10-12 10:00:00',
            'zona' => 'tlaxcala',
            'estado' => 'planeada',
            'id_responsable' => 10,
        ]);
        $usuario = new Usuario;
        $usuario->forceFill(['id_usuario' => 10]);
        $this->withoutMiddleware()->actingAs($usuario);

        $this->postJson('/__phase3/activities/5/state', ['estado' => 'en_curso'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('actividades', ['id_actividad' => 5, 'estado' => 'en_curso']);

        $this->put('/__phase3/activities/5', [
            'titulo' => 'Actividad actualizada',
            'descripcion' => 'Detalles actualizados',
            'fecha_inicio' => '2026-10-12 09:00:00',
            'fecha_fin' => '2026-10-12 11:00:00',
            'zona' => 'ambas',
            'estado' => 'completada',
            'id_usuario_responsable' => '10',
        ])->assertRedirect(route('planning.index', ['mes' => 10, 'anio' => 2026]));
        $this->assertDatabaseHas('actividades', ['id_actividad' => 5, 'titulo' => 'Actividad actualizada', 'estado' => 'completada']);

        $this->delete('/__phase3/activities/5')
            ->assertRedirect(route('planning.index'));
        $this->assertDatabaseMissing('actividades', ['id_actividad' => 5]);
    }

    public function test_servicio_envia_notificacion_de_campana_a_usuarios_activos(): void
    {
        DB::table('usuarios')->insert([
            ['id_usuario' => 10, 'nombre' => 'Ana', 'apellido' => 'Uno', 'email' => 'ana@example.test', 'activo' => true],
            ['id_usuario' => 20, 'nombre' => 'Luis', 'apellido' => 'Dos', 'email' => 'luis@example.test', 'activo' => false],
        ]);
        DB::table('tipos_referencia_notif')->insert([
            'id_tipo_ref' => 1,
            'nombre' => 'campana',
        ]);

        app(NotificacionService::class)->campanaActiva(77, 'Alimentos', '2026-10-31');

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => 10,
            'tipo' => 'campana',
            'asunto' => 'Nueva campaña activa',
            'id_tipo_referencia' => 1,
            'id_referencia' => 77,
            'leida' => false,
        ]);
        $this->assertDatabaseMissing('notificaciones', ['id_usuario' => 20]);
    }

    private function registrarRutasPrueba(): void
    {
        Route::get('/__phase3/notifications/latest', [NotificacionController::class, 'ultimas']);
        Route::post('/__phase3/notifications/{notificacion}/read', [NotificacionController::class, 'marcarLeida']);
        Route::post('/__phase3/notifications/read-all', [NotificacionController::class, 'marcarTodas']);
        Route::post('/__phase3/activities', [ActividadController::class, 'store']);
        Route::put('/__phase3/activities/{actividad}', [ActividadController::class, 'update']);
        Route::delete('/__phase3/activities/{actividad}', [ActividadController::class, 'destroy']);
        Route::post('/__phase3/activities/{actividad}/state', [ActividadController::class, 'actualizarEstado']);
        Route::get('/__phase3/calendar', [ActividadController::class, 'actividadesMes']);
    }

    private function crearEsquemaAislado(): void
    {
        Schema::create('usuarios', function ($table): void {
            $table->unsignedInteger('id_usuario')->primary();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email');
            $table->boolean('activo')->default(true);
        });
        Schema::create('tipos_referencia_notif', function ($table): void {
            $table->increments('id_tipo_ref');
            $table->string('nombre')->unique();
        });
        Schema::create('notificaciones', function ($table): void {
            $table->increments('id_notificacion');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('tipo');
            $table->string('canal')->default('web');
            $table->string('asunto');
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->unsignedInteger('id_referencia')->nullable();
            $table->unsignedInteger('id_tipo_referencia')->nullable();
            $table->timestamp('fecha_envio')->useCurrent();
        });
        Schema::create('actividades', function ($table): void {
            $table->increments('id_actividad');
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('zona');
            $table->string('estado');
            $table->unsignedInteger('id_responsable');
            $table->timestamp('creado_en')->useCurrent();
        });
    }
}
