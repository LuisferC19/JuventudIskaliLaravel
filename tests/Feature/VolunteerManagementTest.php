<?php

namespace Tests\Feature;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VoluntarioController;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VolunteerManagementTest extends TestCase
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
        $this->createIsolatedSchema();
        $this->registerTestRoutes();
        $this->withoutMiddleware();
    }

    public function test_volunteer_profile_supports_create_update_and_logical_delete(): void
    {
        $this->seedUser(1, 'Admin', 'Administrador');
        $this->seedUser(2, 'Ana', 'Voluntario');
        $this->actingAs(Usuario::findOrFail(1));

        $this->post('/voluntarios', [
            'id_usuario' => 2,
            'telefono_contacto' => '222-123-4567',
            'zona_asignada' => 'san_martin',
            'disponibilidad' => 'fines_semana',
            'fecha_ingreso' => '2026-10-15',
            'notas' => 'Apoyo de fin de semana',
        ])->assertRedirect(route('voluntarios.index'));

        $id = (int) DB::table('voluntarios')->value('id_voluntario');
        $this->put("/voluntarios/{$id}", [
            'id_usuario' => 2,
            'telefono_contacto' => '222-765-4321',
            'zona_asignada' => 'tlaxcala',
            'disponibilidad' => 'medio_tiempo',
            'fecha_ingreso' => '2026-10-16',
            'notas' => 'Actualizado',
            'activo' => 1,
        ])->assertRedirect(route('voluntarios.index'));

        $this->assertDatabaseHas('voluntarios', [
            'id_voluntario' => $id,
            'telefono_contacto' => '222-765-4321',
            'zona_asignada' => 'tlaxcala',
            'disponibilidad' => 'medio_tiempo',
        ]);

        $this->put("/voluntarios/{$id}", [
            'id_usuario' => 2,
            'telefono_contacto' => '222-765-4321',
            'zona_asignada' => 'tlaxcala',
            'disponibilidad' => 'medio_tiempo',
            'fecha_ingreso' => '2026-10-16',
            'notas' => 'Actualizado',
        ])->assertRedirect(route('voluntarios.index'));
        $this->assertDatabaseHas('voluntarios', ['id_voluntario' => $id, 'activo' => 0]);

        $this->delete("/voluntarios/{$id}")->assertRedirect(route('voluntarios.index'));
        $this->assertDatabaseHas('voluntarios', ['id_voluntario' => $id, 'activo' => 0]);
    }

    public function test_delivery_assignment_is_recorded_and_can_be_updated_and_removed(): void
    {
        $this->seedUser(1, 'Admin', 'Administrador');
        $this->seedUser(2, 'Ana', 'Voluntario');
        DB::table('voluntarios')->insert([
            'id_voluntario' => 1,
            'id_usuario' => 2,
            'zona_asignada' => 'ambas',
            'disponibilidad' => 'bajo_demanda',
            'activo' => true,
            'fecha_ingreso' => '2026-10-01',
        ]);
        DB::table('entregas')->insert([
            'id_entrega' => 1,
            'estado' => 'programada',
            'fecha_entrega' => '2026-10-20 10:00:00',
        ]);
        $this->actingAs(Usuario::findOrFail(1));

        $this->post('/voluntarios/asignaciones', [
            'id_voluntario' => 1,
            'id_entrega' => 1,
            'fecha_compromiso' => '2026-10-20 10:00:00',
            'observaciones_voluntario' => 'Entregar por la mañana',
        ])->assertRedirect(route('voluntarios.index'));

        $assignmentId = (int) DB::table('asignaciones_voluntario')->value('id_asignacion');
        $this->assertDatabaseHas('asignaciones_voluntario', [
            'id_asignacion' => $assignmentId,
            'id_usuario_asignador' => 1,
            'estado' => 'asignada',
        ]);

        $this->patch("/voluntarios/asignaciones/{$assignmentId}", [
            'estado' => 'aceptada',
        ])->assertRedirect(route('voluntarios.index'));
        $this->assertDatabaseHas('asignaciones_voluntario', ['id_asignacion' => $assignmentId, 'estado' => 'aceptada']);

        $this->delete("/voluntarios/asignaciones/{$assignmentId}")->assertRedirect(route('voluntarios.index'));
        $this->assertDatabaseMissing('asignaciones_voluntario', ['id_asignacion' => $assignmentId]);
    }

    public function test_activity_qr_tokens_are_personal_and_validate_attendance_as_json(): void
    {
        $this->seedUser(1, 'Ana', 'Voluntario');
        $this->seedUser(2, 'Luis', 'Voluntario');
        foreach ([1, 2] as $idUsuario) {
            DB::table('voluntarios')->insert([
                'id_usuario' => $idUsuario,
                'zona_asignada' => 'ambas',
                'disponibilidad' => 'bajo_demanda',
                'activo' => true,
                'fecha_ingreso' => '2026-10-01',
            ]);
        }
        DB::table('actividades')->insert([
            'id_actividad' => 1,
            'titulo' => 'Jornada',
            'fecha_inicio' => '2026-10-20 09:00:00',
            'fecha_fin' => '2026-10-20 12:00:00',
            'estado' => 'planeada',
            'id_responsable' => 1,
        ]);
        $this->actingAs(Usuario::findOrFail(1));

        $result = $this->postJson('/voluntarios/actividades/1/qr')->assertOk()->assertJsonPath('success', true);
        $tokens = $result->json('tokens');
        $this->assertCount(2, $tokens);
        $this->assertNotSame($tokens[0]['token'], $tokens[1]['token']);
        $this->assertDatabaseCount('asistencia_voluntarios', 2);

        $this->postJson('/voluntarios/validar_qr', ['token_qr' => $tokens[0]['token']])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('asistencia_voluntarios', [
            'id_voluntario' => $tokens[0]['id_voluntario'],
            'id_actividad' => 1,
            'metodo' => 'qr',
            'presente' => 1,
        ]);

        $this->postJson('/voluntarios/validar_qr', ['token_qr' => str_repeat('0', 64)])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_assigning_volunteer_role_on_create_or_update_provisions_a_profile(): void
    {
        $this->seedUser(1, 'Admin', 'Administrador');
        $this->seedUser(2, 'Mario', 'Administrador');
        $this->actingAs(Usuario::findOrFail(1));

        $this->post('/usuarios', [
            'id_rol' => 2,
            'nombre' => 'Sofía',
            'apellido' => 'Ruiz',
            'email' => 'sofia@example.test',
            'password' => 'password123',
            'confirmar_password' => 'password123',
            'activo' => 1,
        ])->assertRedirect(route('usuarios.index'));

        $sofiaId = (int) DB::table('usuarios')->where('email', 'sofia@example.test')->value('id_usuario');
        $this->assertDatabaseHas('voluntarios', [
            'id_usuario' => $sofiaId,
            'zona_asignada' => 'ambas',
            'disponibilidad' => 'bajo_demanda',
            'activo' => 1,
        ]);

        $this->put('/usuarios/2', [
            'id_rol' => 2,
            'nombre' => 'Mario',
            'apellido' => 'Administrador',
            'email' => 'mario@example.test',
        ])->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('voluntarios', ['id_usuario' => 2]);
    }

    private function seedUser(int $id, string $nombre, string $rol): void
    {
        $idRol = DB::table('roles')->where('nombre', $rol)->value('id_rol');
        DB::table('usuarios')->insert([
            'id_usuario' => $id,
            'id_rol' => $idRol,
            'nombre' => $nombre,
            'apellido' => 'Usuario',
            'email' => "usuario{$id}@example.test",
            'contrasena_hash' => 'hash',
            'activo' => true,
            'intentos_fallidos' => 0,
            'created_at' => now(),
        ]);
    }

    private function registerTestRoutes(): void
    {
        Route::resource('voluntarios', VoluntarioController::class)->except('show');
        Route::post('voluntarios/asignaciones', [VoluntarioController::class, 'storeAsignacion'])->name('voluntarios.asignaciones.store');
        Route::patch('voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'updateAsignacion'])->name('voluntarios.asignaciones.update');
        Route::delete('voluntarios/asignaciones/{asignacion}', [VoluntarioController::class, 'destroyAsignacion'])->name('voluntarios.asignaciones.destroy');
        Route::get('voluntarios/asistencia/{actividad}', [VoluntarioController::class, 'asistencia'])->name('voluntarios.asistencia');
        Route::post('voluntarios/asistencia', [VoluntarioController::class, 'registrarAsistencia'])->name('voluntarios.asistencias.store');
        Route::post('voluntarios/actividades/{actividad}/qr', [VoluntarioController::class, 'generarQr'])->name('voluntarios.qr.generar');
        Route::post('voluntarios/validar_qr', [VoluntarioController::class, 'validarQr'])->name('voluntarios.validar_qr');
        Route::resource('usuarios', UsuarioController::class)->except('show');
    }

    private function createIsolatedSchema(): void
    {
        Schema::connection('sqlite')->create('roles', function ($table): void {
            $table->increments('id_rol');
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
        });
        DB::table('roles')->insert([
            ['id_rol' => 1, 'nombre' => 'Administrador'],
            ['id_rol' => 2, 'nombre' => 'Voluntario'],
        ]);
        Schema::connection('sqlite')->create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->boolean('activo')->default(true);
            $table->integer('intentos_fallidos')->default(0);
            $table->dateTime('fecha_bloqueo_temporal')->nullable();
            $table->dateTime('ultimo_acceso')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::connection('sqlite')->create('voluntarios', function ($table): void {
            $table->increments('id_voluntario');
            $table->unsignedInteger('id_usuario')->unique();
            $table->string('telefono_contacto', 20)->nullable();
            $table->string('zona_asignada')->default('ambas');
            $table->string('disponibilidad');
            $table->boolean('activo')->default(true);
            $table->date('fecha_ingreso');
            $table->text('notas')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::connection('sqlite')->create('entregas', function ($table): void {
            $table->increments('id_entrega');
            $table->string('estado');
            $table->dateTime('fecha_entrega')->nullable();
        });
        Schema::connection('sqlite')->create('asignaciones_voluntario', function ($table): void {
            $table->increments('id_asignacion');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_entrega');
            $table->unsignedInteger('id_usuario_asignador');
            $table->dateTime('fecha_asignacion');
            $table->dateTime('fecha_compromiso');
            $table->string('estado');
            $table->text('observaciones_voluntario')->nullable();
            $table->dateTime('fecha_actualizacion')->nullable();
        });
        Schema::connection('sqlite')->create('actividades', function ($table): void {
            $table->increments('id_actividad');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('zona')->default('ambas');
            $table->string('estado')->default('planeada');
            $table->unsignedInteger('id_responsable');
            $table->dateTime('creado_en')->nullable();
        });
        Schema::connection('sqlite')->create('asistencia_voluntarios', function ($table): void {
            $table->increments('id_asistencia');
            $table->unsignedInteger('id_voluntario');
            $table->unsignedInteger('id_actividad');
            $table->dateTime('fecha_hora')->useCurrent();
            $table->string('metodo')->default('manual');
            $table->boolean('presente')->default(true);
            $table->string('token_qr', 64)->nullable()->unique();
            $table->text('observaciones')->nullable();
            $table->unique(['id_voluntario', 'id_actividad']);
        });
    }
}
