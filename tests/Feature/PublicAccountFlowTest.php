<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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
        });
        Schema::create('usuarios', function ($table): void {
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
            $table->boolean('leida')->default(false);
        });
        DB::table('roles')->insert([
            ['id_rol' => 1, 'nombre' => 'Administrador'],
            ['id_rol' => 3, 'nombre' => 'Voluntario'],
        ]);
    }

    public function test_registration_creates_an_inactive_volunteer_account(): void
    {
        $this->post(route('registro.store'), [
            'nombre' => 'Ana',
            'apellido' => 'García',
            'email' => ' ANA@example.test ',
            'password' => 'Password1',
            'password_confirm' => 'Password1',
        ])->assertRedirect(route('registro'))
            ->assertSessionHas('success');

        $usuario = DB::table('usuarios')->where('email', 'ana@example.test')->first();
        $this->assertNotNull($usuario);
        $this->assertFalse((bool) $usuario->activo);
        $this->assertSame(3, $usuario->id_rol);
        $this->assertTrue(Hash::check('Password1', $usuario->contrasena_hash));
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
            'activo' => true,
        ]);

        $this->post(route('recuperar_password.store'), ['email' => 'ana@example.test'])
            ->assertRedirect(route('recuperar_password'))
            ->assertSessionHas('success');
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

    public function test_fifth_failed_login_notifies_administrators(): void
    {
        DB::table('usuarios')->insert([
            ['id_usuario' => 1, 'id_rol' => 3, 'nombre' => 'Ana', 'apellido' => 'García', 'email' => 'ana@example.test', 'contrasena_hash' => Hash::make('Password1'), 'activo' => true],
            ['id_usuario' => 2, 'id_rol' => 1, 'nombre' => 'Admin', 'apellido' => 'Iskali', 'email' => 'admin@example.test', 'contrasena_hash' => Hash::make('Password1'), 'activo' => true],
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
