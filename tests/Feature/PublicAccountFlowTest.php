<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\UsuarioController;
use Tests\TestCase;

class PublicAccountFlowTest extends TestCase
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

        Schema::create('roles', function ($table): void {
            $table->increments('id_rol');
            $table->string('nombre');
            $table->string('slug')->unique();
        });
        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->string('estatus')->default('PENDIENTE');
            $table->string('sede')->nullable();
            $table->integer('intentos_fallidos')->default(0);
            $table->dateTime('fecha_bloqueo_temporal')->nullable();
            $table->dateTime('ultimo_acceso')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('voluntarios', function ($table): void {
            $table->increments('id_voluntario');
            $table->unsignedInteger('id_usuario')->unique();
            $table->string('telefono_contacto', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('zona_asignada');
            $table->string('disponibilidad');
            $table->boolean('activo')->default(true);
            $table->date('fecha_ingreso')->nullable();
            $table->dateTime('created_at')->nullable();
        });
        Schema::create('recuperacion_contrasena', function ($table): void {
            $table->increments('id_recuperacion');
            $table->unsignedInteger('id_usuario');
            $table->string('token')->unique();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_expiracion');
            $table->boolean('usado')->default(false);
        });
        Schema::create('historial_accesos', function ($table): void {
            $table->increments('id_acceso');
            $table->unsignedInteger('id_usuario');
            $table->string('tipo_accion');
            $table->dateTime('fecha_hora')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->boolean('exitoso')->default(true);
        });
        Schema::create('notificaciones', function ($table): void {
            $table->increments('id_notificacion');
            $table->unsignedInteger('id_usuario')->nullable();
            $table->string('tipo');
            $table->string('canal')->default('web');
            $table->string('asunto');
            $table->text('mensaje');
            $table->unsignedInteger('id_referencia')->nullable();
            $table->unsignedInteger('id_tipo_referencia')->nullable();
            $table->boolean('leida')->default(false);
        });
        DB::table('roles')->insert([
            ['id_rol' => 1, 'nombre' => 'Superadministrador', 'slug' => 'superadmin'],
            ['id_rol' => 2, 'nombre' => 'Directiva', 'slug' => 'directiva'],
            ['id_rol' => 3, 'nombre' => 'Voluntario / Practicante', 'slug' => 'voluntario'],
        ]);
        Route::post('/__test/usuarios/{usuario}/aprobar', [UsuarioController::class, 'aprobar']);
    }

    public function test_registration_creates_a_pending_volunteer_account(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 1,
            'id_rol' => 2,
            'nombre' => 'Directiva',
            'apellido' => 'Iskali',
            'email' => 'directiva@example.test',
            'contrasena_hash' => Hash::make('Password1'),
            'estatus' => 'ACTIVO',
        ]);

        $this->post(route('registro.store'), [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => ' ANA@example.test ',
            'password' => 'Password1',
            'password_confirm' => 'Password1',
            'fecha_nacimiento' => '2000-05-10',
            'telefono_contacto' => '2221234567',
            'zona_asignada' => 'san_martin',
        ])->assertRedirect(route('registro'))
            ->assertSessionHas('success');

        $usuario = DB::table('usuarios')->where('email', 'ana@example.test')->first();
        $this->assertNotNull($usuario);
        $this->assertSame('PENDIENTE', $usuario->estatus);
        $this->assertSame(3, $usuario->id_rol);
        $this->assertTrue(Hash::check('Password1', $usuario->contrasena_hash));
        $this->assertDatabaseHas('voluntarios', [
            'id_usuario' => $usuario->id_usuario,
            'telefono_contacto' => '2221234567',
            'fecha_nacimiento' => '2000-05-10',
            'zona_asignada' => 'san_martin',
            'disponibilidad' => 'bajo_demanda',
            'activo' => 0,
            'fecha_ingreso' => null,
        ]);
        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => 1,
            'asunto' => 'Nueva solicitud de voluntariado',
        ]);

        $this->post("/__test/usuarios/{$usuario->id_usuario}/aprobar")
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $usuario->id_usuario,
            'estatus' => 'ACTIVO',
        ]);
        $this->assertDatabaseHas('voluntarios', [
            'id_usuario' => $usuario->id_usuario,
            'activo' => 1,
        ]);
        $this->assertSame(
            now()->toDateString(),
            \Illuminate\Support\Carbon::parse(
                DB::table('voluntarios')->where('id_usuario', $usuario->id_usuario)->value('fecha_ingreso')
            )->toDateString()
        );
    }

    public function test_recovery_request_stores_expiring_token_without_revealing_unknown_emails(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 1,
            'id_rol' => 3,
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@example.test',
            'contrasena_hash' => Hash::make('Password1'),
            'estatus' => 'ACTIVO',
        ]);

        $this->post(route('recuperar_password.store'), ['email' => 'ana@example.test'])
            ->assertRedirect(route('recuperar_password'))
            ->assertSessionHas('success')
            ->assertSessionMissing('reset_url');
        $this->assertDatabaseHas('recuperacion_contrasena', [
            'id_usuario' => 1,
            'usado' => false,
        ]);
        $this->assertDatabaseHas('historial_accesos', [
            'id_usuario' => 1,
            'tipo_accion' => 'recuperacion',
            'exitoso' => true,
        ]);

        $this->post(route('recuperar_password.store'), ['email' => 'desconocido@example.test'])
            ->assertRedirect(route('recuperar_password'))
            ->assertSessionHas('success');
        $this->assertSame(1, DB::table('recuperacion_contrasena')->count());
    }

    public function test_approval_provisions_a_missing_volunteer_profile(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 1,
            'id_rol' => 3,
            'nombre' => 'Luis',
            'apellido' => 'Pérez',
            'email' => 'luis@example.test',
            'contrasena_hash' => Hash::make('Password1'),
            'estatus' => 'PENDIENTE',
        ]);

        $this->post('/__test/usuarios/1/aprobar')
            ->assertRedirect(route('usuarios.index'));

        $perfil = DB::table('voluntarios')->where('id_usuario', 1)->first();
        $this->assertNotNull($perfil);
        $this->assertSame('ambas', $perfil->zona_asignada);
        $this->assertSame('bajo_demanda', $perfil->disponibilidad);
        $this->assertSame(1, (int) $perfil->activo);
        $this->assertSame(
            now()->toDateString(),
            \Illuminate\Support\Carbon::parse($perfil->fecha_ingreso)->toDateString()
        );
    }

    public function test_valid_recovery_token_changes_password_and_cannot_be_reused(): void
    {
        DB::table('usuarios')->insert([
            'id_usuario' => 1,
            'id_rol' => 3,
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => 'ana@example.test',
            'contrasena_hash' => Hash::make('Password1'),
            'estatus' => 'ACTIVO',
        ]);
        DB::table('recuperacion_contrasena')->insert([
            'id_usuario' => 1,
            'token' => str_repeat('a', 64),
            'fecha_solicitud' => now(),
            'fecha_expiracion' => now()->addHour(),
            'usado' => false,
        ]);

        $this->get(route('recuperar_password.reset', str_repeat('a', 64)))
            ->assertOk()
            ->assertSee('Restablecer contraseña')
            ->assertSee('Login_Styles', false);

        $this->post(route('recuperar_password.update', str_repeat('a', 64)), [
            'password' => 'Password2',
            'password_confirm' => 'Password2',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Password2', DB::table('usuarios')->where('id_usuario', 1)->value('contrasena_hash')));
        $this->assertDatabaseHas('recuperacion_contrasena', [
            'token' => str_repeat('a', 64),
            'usado' => true,
        ]);

        $this->get(route('recuperar_password.reset', str_repeat('a', 64)))
            ->assertNotFound();
    }

    public function test_fifth_failed_login_notifies_administrators(): void
    {
        DB::table('usuarios')->insert([
            ['id_usuario' => 1, 'id_rol' => 3, 'nombre' => 'Ana', 'apellido' => 'García', 'email' => 'ana@example.test', 'contrasena_hash' => Hash::make('Password1'), 'estatus' => 'ACTIVO'],
            ['id_usuario' => 2, 'id_rol' => 1, 'nombre' => 'Admin', 'apellido' => 'Iskali', 'email' => 'admin@example.test', 'contrasena_hash' => Hash::make('Password1'), 'estatus' => 'ACTIVO'],
        ]);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post(route('login.authenticate'), [
                'email' => 'ana@example.test',
                'password' => 'incorrecta',
            ]);
        }

        $this->assertDatabaseHas('notificaciones', [
            'id_usuario' => 2,
            'tipo' => 'alerta',
            'asunto' => 'Cuenta bloqueada por intentos fallidos',
        ]);
    }
}
