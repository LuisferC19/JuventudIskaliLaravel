<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthLoginTest extends TestCase
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

        Schema::create('usuarios', function ($table): void {
            $table->increments('id_usuario');
            $table->unsignedInteger('id_rol');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('email')->unique();
            $table->string('contrasena_hash');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('intentos_fallidos')->default(0);
            $table->timestamp('fecha_bloqueo_temporal')->nullable();
            $table->timestamp('ultimo_acceso')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('historial_accesos', function ($table): void {
            $table->increments('id_acceso');
            $table->unsignedInteger('id_usuario');
            $table->string('tipo_accion');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('exitoso')->default(true);
        });
    }

    public function test_login_accepts_bcrypt_hashes_with_the_2b_prefix(): void
    {
        $hash = password_hash('correct-password', PASSWORD_BCRYPT);
        $usuario = $this->crearUsuario(substr_replace($hash, '2b', 1, 2));

        $this->post(route('login.authenticate'), [
            'email' => $usuario->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertDatabaseHas('historial_accesos', [
            'id_usuario' => $usuario->id_usuario,
            'tipo_accion' => 'login',
            'exitoso' => true,
        ]);
        $this->assertSame(0, $usuario->fresh()->intentos_fallidos);
    }

    public function test_failed_login_records_attempt_and_access_history(): void
    {
        $usuario = $this->crearUsuario(password_hash('correct-password', PASSWORD_BCRYPT));

        $this->from(route('login'))
            ->post(route('login.authenticate'), [
                'email' => $usuario->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, $usuario->fresh()->intentos_fallidos);
        $this->assertDatabaseHas('historial_accesos', [
            'id_usuario' => $usuario->id_usuario,
            'tipo_accion' => 'fallo_login',
            'exitoso' => false,
        ]);
    }

    private function crearUsuario(string $hash): Usuario
    {
        return Usuario::create([
            'id_rol' => 1,
            'nombre' => 'Prueba',
            'apellido' => 'Login',
            'email' => 'login@example.test',
            'contrasena_hash' => $hash,
            'activo' => true,
            'intentos_fallidos' => 0,
        ]);
    }
}
