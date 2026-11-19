<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificarPermiso;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseThreeWorkflowTest extends TestCase
{
    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        $this->withoutMiddleware(VerificarPermiso::class);
        $this->crearEsquema();

        $this->usuario = new Usuario;
        $this->usuario->forceFill([
            'id_usuario' => 1,
            'id_rol' => 1,
            'nombre' => 'Admin',
            'apellido' => 'Pruebas',
            'email' => 'admin@example.test',
            'estatus' => 'ACTIVO',
        ]);
        $this->actingAs($this->usuario);
    }

    public function test_planning_creates_activities_and_returns_monthly_json(): void
    {
        $this->post(route('planning.store'), [
            'titulo' => 'Jornada comunitaria',
            'descripcion' => 'Apoyo local',
            'fecha_inicio' => '2026-10-12T09:00',
            'fecha_fin' => '2026-10-12T12:00',
            'zona' => 'tlaxcala',
            'estado' => 'planeada',
            'puntos' => 10,
        ])->assertRedirect(route('planning.index', ['mes' => 10, 'anio' => 2026]));

        $this->getJson(route('planning.calendario', ['mes' => 10, 'anio' => 2026]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('actividades.0.titulo', 'Jornada comunitaria')
            ->assertJsonPath('actividades.0.puntos', 10);

        $this->get(route('planning.index', ['mes' => 10, 'anio' => 2026]))
            ->assertOk()
            ->assertSee('Jornada comunitaria');
        $this->get(route('planning.create'))
            ->assertOk()
            ->assertSee('Registrar actividad');
    }

    public function test_planning_rejects_deleting_an_activity_with_attendance(): void
    {
        DB::table('actividades')->insert([
            'id_actividad' => 9,
            'titulo' => 'Actividad con asistencia',
            'fecha_inicio' => '2026-10-12 09:00:00',
            'fecha_fin' => '2026-10-12 12:00:00',
            'zona' => 'ambas',
            'estado' => 'completada',
            'id_responsable' => 1,
        ]);
        DB::table('asistencia_voluntarios')->insert([
            'id_voluntario' => 1,
            'id_actividad' => 9,
            'metodo' => 'manual',
            'presente' => true,
        ]);

        $this->delete(route('planning.destroy', 9))
            ->assertSessionHasErrors('actividad');

        $this->assertDatabaseHas('actividades', ['id_actividad' => 9]);
    }

    public function test_qr_attendance_is_persisted_and_token_cannot_be_reused(): void
    {
        DB::table('voluntarios')->insert([
            'id_voluntario' => 1,
            'id_usuario' => 2,
            'zona_asignada' => 'ambas',
            'disponibilidad' => 'fines_semana',
            'activo' => true,
            'fecha_ingreso' => '2026-01-01',
        ]);
        DB::table('actividades')->insert([
            'id_actividad' => 1,
            'titulo' => 'Entrega de apoyo',
            'fecha_inicio' => '2026-10-12 09:00:00',
            'fecha_fin' => '2026-10-12 12:00:00',
            'zona' => 'ambas',
            'estado' => 'planeada',
            'id_responsable' => 1,
        ]);

        $response = $this->postJson(route('voluntarios.qr.generar'), [
            'id_voluntario' => 1,
            'id_actividad' => 1,
        ])->assertOk()->assertJsonPath('success', true);
        $token = $response->json('token');

        $this->assertSame(64, strlen($token));
        $this->postJson(route('voluntarios.qr.validar'), ['token_qr' => $token])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->postJson(route('voluntarios.qr.validar'), ['token_qr' => $token])
            ->assertUnprocessable();
        $this->assertTrue((bool) DB::table('asistencia_voluntarios')->value('presente'));
    }

    public function test_marking_notification_read_is_scoped_to_current_user(): void
    {
        DB::table('notificaciones')->insert([
            ['id_notificacion' => 1, 'id_usuario' => 1, 'tipo' => 'sistema', 'canal' => 'web', 'asunto' => 'Propia', 'mensaje' => 'Mensaje', 'leida' => false],
            ['id_notificacion' => 2, 'id_usuario' => 2, 'tipo' => 'sistema', 'canal' => 'web', 'asunto' => 'Ajena', 'mensaje' => 'Mensaje', 'leida' => false],
        ]);

        $this->postJson(route('notificaciones.leer', 1))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 1, 'leida' => true]);
        $this->assertDatabaseHas('notificaciones', ['id_notificacion' => 2, 'leida' => false]);
    }

    public function test_printable_campaign_report_uses_date_filters(): void
    {
        DB::table('campanas')->insert([
            'nombre' => 'Campaña de invierno',
            'tipo_meta' => 'especie',
            'fecha_inicio' => '2026-10-01',
            'fecha_cierre' => '2026-10-31',
            'estado' => 'activa',
            'meta_cantidad' => 5000,
        ]);

        $this->get(route('reportes.generar', [
            'tipo' => 'campanas',
            'desde' => '2026-10-01',
            'hasta' => '2026-10-31',
        ]))->assertOk()->assertSee('Campaña de invierno');
    }

    private function crearEsquema(): void
    {
        Schema::create('roles', function ($table): void {
            $table->increments('id_rol');
            $table->string('nombre');
            $table->string('slug')->nullable();
        });
        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->string('estatus')->default('ACTIVO');
            $table->string('sede')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('permisos', function ($table): void {
            $table->increments('id_permiso');
            $table->string('slug')->unique();
            $table->string('modulo');
            $table->string('descripcion')->nullable();
        });
        Schema::create('rol_permiso', function ($table): void {
            $table->unsignedInteger('id_rol');
            $table->unsignedInteger('id_permiso');
            $table->primary(['id_rol', 'id_permiso']);
        });
        Schema::create('actividades', function ($table): void {
            $table->increments('id_actividad');
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('zona');
            $table->string('estado');
            $table->unsignedTinyInteger('puntos')->default(5);
            $table->unsignedInteger('id_responsable');
            $table->timestamp('creado_en')->nullable();
        });
        Schema::create('campanas', function ($table): void {
            $table->increments('id_campana');
            $table->string('nombre');
            $table->string('tipo_meta');
            $table->date('fecha_inicio');
            $table->date('fecha_cierre');
            $table->string('estado');
            $table->decimal('meta_cantidad', 12, 2)->default(0);
        });
        Schema::create('voluntarios', function ($table): void {
            $table->increments('id_voluntario');
            $table->unsignedInteger('id_usuario')->unique();
            $table->string('telefono_contacto')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('zona_asignada');
            $table->string('disponibilidad');
            $table->boolean('activo')->default(true);
            $table->date('fecha_ingreso');
            $table->text('notas')->nullable();
        });
        Schema::create('asistencia_voluntarios', function ($table): void {
            $table->increments('id_asistencia');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_actividad');
            $table->dateTime('fecha_hora')->nullable();
            $table->string('metodo');
            $table->boolean('presente')->default(false);
            $table->string('token_qr', 64)->nullable()->unique();
            $table->unique(['id_voluntario', 'id_actividad']);
        });
        Schema::create('bolsa_voluntario', function ($table): void {
            $table->increments('id_bolsa');
            $table->unsignedInteger('id_voluntario')->unique();
            $table->unsignedInteger('puntos_canjeables')->default(0);
            $table->unsignedInteger('puntos_campamento')->default(0);
            $table->unsignedTinyInteger('faltas_injustificadas')->default(0);
            $table->boolean('campamento_bloqueado')->default(false);
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('movimientos_puntos', function ($table): void {
            $table->increments('id_movimiento_puntos');
            $table->unsignedInteger('id_voluntario');
            $table->string('tipo');
            $table->integer('puntos_canjeables')->default(0);
            $table->integer('puntos_campamento')->default(0);
            $table->unsignedInteger('id_actividad')->nullable();
            $table->unsignedInteger('id_canje')->nullable();
            $table->string('motivo')->nullable();
            $table->unsignedInteger('id_usuario')->nullable();
            $table->dateTime('fecha');
            $table->unique(['id_voluntario', 'id_actividad', 'tipo']);
        });
        Schema::create('notificaciones', function ($table): void {
            $table->increments('id_notificacion');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('tipo');
            $table->string('canal')->default('web');
            $table->string('asunto');
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->dateTime('fecha_envio')->nullable();
        });
    }
}
